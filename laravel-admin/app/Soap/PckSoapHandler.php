<?php

namespace App\Soap;

use App\Jobs\ProcessInboundArticlePayload;
use App\Jobs\ProcessInboundImagePayload;
use App\Jobs\ProcessStockUpdatePayload;
use App\Models\Order;
use App\Models\PckInboundPayload;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapFault;

class PckSoapHandler
{
    private Request $request;

    private ?string $tenantKey;

    private ?TenantContext $tenantContext = null;

    public function __construct(Request $request, ?string $tenantKey = null)
    {
        $this->request = $request;
        $this->tenantKey = $tenantKey;
    }

    /**
     * Convert stdClass objects to arrays recursively
     */
    private function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map([$this, 'objectToArray'], $data);
        }

        return $data;
    }

    /**
     * Authenticate and resolve tenant context
     */
    private function authenticate(array $params): TenantContext
    {
        // Extract authentication parameters
        $login = $params['login'] ?? null;
        $password = $params['password'] ?? null;

        if (! is_numeric($login) || empty($password)) {
            throw new SoapFault('Client', 'Invalid authentication parameters');
        }

        // Resolve tenant context
        $tenant = TenantResolver::resolveWithFallback($params, $this->request);

        if (! $tenant) {
            Log::warning('PCK SOAP: Tenant resolution failed', [
                'login' => $login,
                'tenant_key' => $this->tenantKey,
                'ip' => $this->request->ip(),
            ]);
            throw new SoapFault('Client', 'Tenant not found');
        }

        // Authenticate credentials
        $authenticated = \App\Models\PckCredential::authenticate(
            $tenant->getTenantId(),
            $tenant->getPckUsername(),
            $password,
            $tenant->getPckLicense()
        );

        if (! $authenticated) {
            Log::warning('PCK SOAP: Authentication failed', [
                'tenant_id' => $tenant->getTenantId(),
                'username' => $tenant->getPckUsername(),
                'ip' => $this->request->ip(),
            ]);
            throw new SoapFault('Client', 'Authentication failed');
        }

        // Validate tenant access (IP whitelist, etc.)
        if (! TenantResolver::validateTenantAccess($tenant, $this->request)) {
            Log::warning('PCK SOAP: Access denied', [
                'tenant_id' => $tenant->getTenantId(),
                'ip' => $this->request->ip(),
                'reason' => 'IP not whitelisted',
            ]);
            throw new SoapFault('Client', 'Access denied');
        }

        $this->tenantContext = $tenant;

        return $tenant;
    }

    /**
     * Create standard OK response
     */
    private function createOkResponse(int $deltaId = 0): array
    {
        return [
            'deltaId' => $deltaId,
            'errorHelpLink' => '',
            'errorMessage' => '',
            'humanErrorMessage' => '',
            'operationResult' => 0, // 0 = OK
        ];
    }

    /**
     * Create error response
     */
    private function createErrorResponse(string $message, int $operationResult = 1, int $deltaId = 0): array
    {
        return [
            'deltaId' => $deltaId,
            'errorHelpLink' => '',
            'errorMessage' => $message,
            'humanErrorMessage' => $message,
            'operationResult' => $operationResult,
        ];
    }

    /**
     * Send Article - Document/Literal Wrapped signature
     * Takes: $params with login, password, article properties
     * Returns: insertUpdateResponse
     */
    public function sendArticle($params)
    {
        try {
            // Document/literal wrapped: extract parameters
            $login = $params->login ?? null;
            $password = $params->password ?? null;
            $article = $params->article ?? null;

            $tenant = $this->authenticate([
                'login' => $login,
                'password' => $password,
            ]);

            // Convert stdClass to array if needed
            $articleArray = $this->objectToArray($article);

            $payload = [
                'login' => $login,
                'password' => $password,
                'article' => $articleArray,
                'timestamp' => $articleArray['timestamp'] ?? time(),
            ];

            // Store payload and enqueue job for async processing
            $result = PckInboundPayload::findOrCreateIdempotent(
                $tenant->getTenantId(),
                PckInboundPayload::METHOD_SEND_ARTICLE,
                $payload
            );

            if ($result['is_duplicate']) {
                Log::info('PCK SOAP: Duplicate sendArticle request ignored', [
                    'tenant_id' => $tenant->getTenantId(),
                    'article_id' => $articleArray['articleId'] ?? 'unknown',
                    'idempotency_key' => $result['payload']->idempotency_key,
                ]);
            } else {
                // Temporarily disable queue job to test - just mark as processed
                // ProcessInboundArticlePayload::dispatch($result['payload']->id)
                //     ->onQueue('pck-inbound');

                Log::info('PCK SOAP: sendArticle received (queue disabled for testing)', [
                    'tenant_id' => $tenant->getTenantId(),
                    'article_id' => $articleArray['articleId'] ?? 'unknown',
                    'payload_id' => $result['payload']->id,
                    'article_data_keys' => array_keys($articleArray),
                ]);

                // Mark as processed without queue job
                $result['payload']->markProcessed();
            }

            // Create insertUpdateResponse exactly like .NET version
            $svar = new \stdClass;
            $svar->deltaId = $articleArray['articleId'] ?? 0;
            $svar->errorHelpLink = '';
            $svar->errorMessage = '';
            $svar->humanErrorMessage = '';
            $svar->operationResult = 0; // 0 = OK

            return $svar;

        } catch (SoapFault $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PCK SOAP: sendArticle error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'article' => $this->objectToArray($article ?? null),
            ]);
            $errorResponse = new \stdClass;
            $errorResponse->deltaId = 0;
            $errorResponse->errorHelpLink = '';
            $errorResponse->errorMessage = 'Internal server error';
            $errorResponse->humanErrorMessage = 'Internal server error';
            $errorResponse->operationResult = 2; // 2 = Temporary error

            return $errorResponse;
        }
    }

    /**
     * Send Image - receives product image from PCK
     * Returns: insertUpdateResponse (according to PDF documentation)
     */
    public function sendImage($login, $password, $image, $articleid)
    {
        try {
            $tenant = $this->authenticate([
                'login' => $login,
                'password' => $password,
            ]);

            $payload = [
                'login' => $login,
                'password' => $password,
                'image' => $image,
                'articleid' => $articleid,
                'timestamp' => time(),
            ];

            // Store payload and enqueue job
            $result = PckInboundPayload::findOrCreateIdempotent(
                $tenant->getTenantId(),
                PckInboundPayload::METHOD_SEND_IMAGE,
                $payload
            );

            if (! $result['is_duplicate']) {
                ProcessInboundImagePayload::dispatch($result['payload']->id)
                    ->onQueue('pck-inbound');

                Log::info('PCK SOAP: sendImage queued for processing', [
                    'tenant_id' => $tenant->getTenantId(),
                    'article_id' => $articleid,
                    'payload_id' => $result['payload']->id,
                    'image_size' => strlen($image),
                ]);
            }

            return $this->createOkResponse($articleid);

        } catch (SoapFault $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PCK SOAP: sendImage error', [
                'error' => $e->getMessage(),
                'article_id' => $articleid ?? null,
            ]);

            return $this->createErrorResponse('Internal server error', 2);
        }
    }

    /**
     * Update Stock Count - receives stock updates from PCK
     * Returns: insertUpdateResponse (according to PDF documentation)
     */
    public function updateStockCount($login, $password, $updateStock)
    {
        try {
            $tenant = $this->authenticate([
                'login' => $login,
                'password' => $password,
            ]);

            // Convert stdClass to array if needed
            $updateStockArray = $this->objectToArray($updateStock);

            $payload = [
                'login' => $login,
                'password' => $password,
                'updateStock' => $updateStockArray,
                'timestamp' => $updateStockArray['timestamp'] ?? time(),
            ];

            // Store payload and enqueue job
            $result = PckInboundPayload::findOrCreateIdempotent(
                $tenant->getTenantId(),
                PckInboundPayload::METHOD_UPDATE_STOCK_COUNT,
                $payload
            );

            if (! $result['is_duplicate']) {
                ProcessStockUpdatePayload::dispatch($result['payload']->id)
                    ->onQueue('pck-inbound');

                Log::info('PCK SOAP: updateStockCount queued for processing', [
                    'tenant_id' => $tenant->getTenantId(),
                    'article_id' => $updateStockArray['articleId'] ?? 'unknown',
                    'stock_count' => $updateStockArray['count'] ?? 'unknown',
                    'payload_id' => $result['payload']->id,
                ]);
            }

            return $this->createOkResponse($updateStockArray['articleId'] ?? 0);

        } catch (SoapFault $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PCK SOAP: updateStockCount error', [
                'error' => $e->getMessage(),
                'updateStock' => $this->objectToArray($updateStock ?? null),
            ]);

            return $this->createErrorResponse('Internal server error', 2);
        }
    }

    /**
     * Get Orders - returns new orders to PCK immediately
     * Returns: webOrdersReturn (according to PDF documentation)
     */
    public function getOrders($login, $password, $computerName)
    {
        try {
            $tenant = $this->authenticate([
                'login' => $login,
                'password' => $password,
            ]);

            // Get new orders for this tenant
            $orders = Order::getOrdersForPckExport($tenant->getTenantId(), 100);

            $webOrders = [];
            foreach ($orders as $order) {
                $webOrders[] = [
                    'deltaOrderId' => $order->ordreid,
                    'contactName' => $order->full_name,
                    'contactId' => null,
                    'email' => $order->epost,
                    'phone' => $order->telefon,
                    'paymentMethod' => $this->mapPaymentMethod($order->paymentmethod),
                    'freightCost' => 0.0,
                    'extraCost' => 0.0,
                    'orderLines' => $this->getOrderLines($order),
                ];

                // Mark as exported
                $order->markAsExportedToPck();
            }

            Log::info('PCK SOAP: getOrders returned orders', [
                'tenant_id' => $tenant->getTenantId(),
                'order_count' => count($webOrders),
                'computer_name' => $computerName,
            ]);

            return [
                'insertUpdate' => $this->createOkResponse(),
                'listWebOrders' => $webOrders,
            ];

        } catch (SoapFault $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PCK SOAP: getOrders error', [
                'error' => $e->getMessage(),
                'computer_name' => $computerName ?? null,
            ]);

            return [
                'insertUpdate' => $this->createErrorResponse('Internal server error', 2),
                'listWebOrders' => [],
            ];
        }
    }

    /**
     * Update Order Status - receives order status updates from PCK
     * Returns: updateOrderResponse (according to PDF documentation)
     */
    public function updateOrderStatus($login, $password, $updateOrder)
    {
        try {
            $tenant = $this->authenticate([
                'login' => $login,
                'password' => $password,
            ]);

            // Convert stdClass to array if needed
            $updateOrderArray = $this->objectToArray($updateOrder);

            $orderId = $updateOrderArray['deltaOrderId'] ?? null;
            $statusId = $updateOrderArray['orderStatusId'] ?? null;
            $message = $updateOrderArray['message'] ?? '';

            if (! $orderId) {
                throw new SoapFault('Client', 'Order ID is required');
            }

            // Find the order
            $order = Order::where('site', $tenant->getTenantId())
                ->where('ordreid', $orderId)
                ->first();

            if (! $order) {
                Log::warning('PCK SOAP: Order not found for status update', [
                    'tenant_id' => $tenant->getTenantId(),
                    'order_id' => $orderId,
                    'status_id' => $statusId,
                ]);

                return $this->createErrorResponse('Order not found', 1);
            }

            // Handle different status types
            $this->handleOrderStatusUpdate($order, $statusId, $message, $updateOrderArray);

            Log::info('PCK SOAP: Order status updated', [
                'tenant_id' => $tenant->getTenantId(),
                'order_id' => $orderId,
                'status_id' => $statusId,
                'message' => $message,
            ]);

            return [
                'insertUpdate' => $this->createOkResponse($orderId),
                'amount' => 0.0,
                'authorizationId' => '',
                'paymentMethod' => '',
            ];

        } catch (SoapFault $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PCK SOAP: updateOrderStatus error', [
                'error' => $e->getMessage(),
                'updateOrder' => $this->objectToArray($updateOrder ?? null),
            ]);

            return [
                'insertUpdate' => $this->createErrorResponse('Internal server error', 2),
                'amount' => 0.0,
                'authorizationId' => '',
                'paymentMethod' => '',
            ];
        }
    }

    /**
     * Create Webshop - Document/Literal Wrapped signature
     * Takes: $params with $params->webcompany property
     * Returns: createDeltasWebshopResponse
     */
    public function createWebshop($params)
    {
        try {
            // Document/literal wrapped: extract webcompany from params
            $webcompany = is_object($params) && isset($params->webcompany)
                ? $params->webcompany
                : $params; // fallback

            $companyArray = $this->objectToArray($webcompany);

            Log::info('PCK SOAP: createWebshop called', [
                'company_data' => $companyArray,
                'params_structure' => $this->objectToArray($params),
                'ip' => $this->request->ip(),
            ]);

            // Create response exactly like .NET version - let SoapServer serialize
            $insertUpdate = new \stdClass;
            $insertUpdate->deltaId = 12345; // ID for webshop
            $insertUpdate->errorHelpLink = '';
            $insertUpdate->errorMessage = '';
            $insertUpdate->humanErrorMessage = '';
            $insertUpdate->operationResult = 0; // 0 = OK

            $svar = new \stdClass;
            $svar->adminUserName = 'admin@aroiasia.no';
            $svar->adminUserPassword = 'Contact system administrator';
            $svar->deltasoftId = 12345;
            $svar->insertUpdate = $insertUpdate; // Must be named exactly "insertUpdate"
            $svar->password = 'AroiWebshop2024';

            Log::info('PCK SOAP: createWebshop response', [
                'response_structure' => get_object_vars($svar),
                'insertUpdate_structure' => get_object_vars($insertUpdate),
                'ip' => $this->request->ip(),
            ]);

            return $svar;

        } catch (\Exception $e) {
            Log::error('PCK SOAP: createWebshop error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params_structure' => $this->objectToArray($params ?? null),
            ]);

            $errorInsertUpdate = new \stdClass;
            $errorInsertUpdate->deltaId = 0;
            $errorInsertUpdate->errorHelpLink = '';
            $errorInsertUpdate->errorMessage = 'Setup error: '.$e->getMessage();
            $errorInsertUpdate->humanErrorMessage = 'Setup error: '.$e->getMessage();
            $errorInsertUpdate->operationResult = 2; // 2 = Temporary error

            $errorResponse = new \stdClass;
            $errorResponse->adminUserName = '';
            $errorResponse->adminUserPassword = '';
            $errorResponse->deltasoftId = 0;
            $errorResponse->insertUpdate = $errorInsertUpdate;
            $errorResponse->password = '';

            return $errorResponse;
        }
    }

    /**
     * Remove Article - removes article from webshop
     * Returns: insertUpdateResponse (according to PDF documentation)
     */
    public function removeArticle($login, $password, $articleid)
    {
        try {
            $tenant = $this->authenticate([
                'login' => $login,
                'password' => $password,
            ]);

            $payload = [
                'login' => $login,
                'password' => $password,
                'articleid' => $articleid,
                'timestamp' => time(),
            ];

            // Store payload and enqueue job
            $result = PckInboundPayload::findOrCreateIdempotent(
                $tenant->getTenantId(),
                PckInboundPayload::METHOD_REMOVE_ARTICLE,
                $payload
            );

            if (! $result['is_duplicate']) {
                // Remove mapping immediately as this is a removal
                \App\Models\PckEntityMap::removeByPckArticle($tenant->getTenantId(), $articleid);
                $result['payload']->markProcessed();

                Log::info('PCK SOAP: removeArticle processed', [
                    'tenant_id' => $tenant->getTenantId(),
                    'article_id' => $articleid,
                ]);
            }

            return $this->createOkResponse($articleid);

        } catch (SoapFault $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PCK SOAP: removeArticle error', [
                'error' => $e->getMessage(),
                'article_id' => $articleid ?? null,
            ]);

            return $this->createErrorResponse('Internal server error', 2);
        }
    }

    /**
     * Map payment method from order to PCK format
     */
    private function mapPaymentMethod(?string $paymentMethod): int
    {
        return match (strtolower($paymentMethod ?? '')) {
            'cod', 'cash_on_delivery' => 2, // COD
            'credit' => 3, // Credit Order
            default => 1, // Prepaid
        };
    }

    /**
     * Get order lines for PCK format (simplified implementation)
     */
    private function getOrderLines(Order $order): array
    {
        // This is a simplified implementation
        // In a real scenario, you'd have order_lines table with detailed product info
        return [
            [
                'orderLineId' => 1,
                'articleId' => 1,
                'count' => 1,
                'qty' => 1.0,
                'price' => 100.0,
                'discount' => 0.0,
            ],
        ];
    }

    /**
     * Handle different order status updates
     */
    private function handleOrderStatusUpdate(Order $order, int $statusId, string $message, array $updateOrder): void
    {
        switch ($statusId) {
            case 4: // Order received successfully
                $order->update(['ordrestatus' => 'received']);
                break;

            case 5: // Normal delivery
            case 3: // Fully delivered
                $order->update([
                    'ordrestatus' => 'delivered',
                    'curltime' => now(),
                ]);
                break;

            case 7: // Failed - message to admin
            case 8: // Failed - message to customer
                $order->markPckExportFailed($message);
                break;

            case 11: // Resend credit applicant
                // Handle credit applicant resend
                $order->update(['ordrestatus' => 'credit_resend']);
                break;

            default:
                Log::warning('PCK SOAP: Unknown order status ID', [
                    'status_id' => $statusId,
                    'order_id' => $order->ordreid,
                ]);
        }
    }
}
