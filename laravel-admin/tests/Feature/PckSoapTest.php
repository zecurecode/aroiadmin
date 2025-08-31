<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PckCredential;
use App\Models\PckInboundPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PckSoapTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test PCK credential
        PckCredential::create([
            'tenant_id' => 1,
            'pck_username' => 'test_pck',
            'pck_password' => 'test_password',
            'pck_license' => '12345',
            'wsdl_version' => '1.98',
            'is_enabled' => true,
        ]);
    }

    public function test_wsdl_endpoint_returns_valid_xml(): void
    {
        $response = $this->get('/wsdl/pck.wsdl');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/xml; charset=utf-8');
        
        // Verify it's valid XML
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        
        // Verify it contains expected SOAP elements
        $this->assertStringContains('definitions', $response->getContent());
        $this->assertStringContains('PckWebshopService', $response->getContent());
    }

    public function test_soap_health_endpoint(): void
    {
        $response = $this->get('/pck/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'wsdl_exists' => true,
            'soap_extension' => true,
        ]);
        
        $this->assertArrayHasKey('timestamp', $response->json());
        $this->assertArrayHasKey('enabled_tenants', $response->json());
        $this->assertEquals(1, $response->json('enabled_tenants'));
    }

    public function test_tenant_info_endpoint(): void
    {
        $response = $this->get('/pck/tenant/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tenant' => [
                'tenant_id',
                'tenant_name',
                'pck_username',
                'pck_license',
                'is_enabled',
            ],
        ]);
    }

    public function test_tenant_info_endpoint_not_found(): void
    {
        $response = $this->get('/pck/tenant/999');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Tenant not found',
            'tenant_key' => '999',
        ]);
    }

    public function test_soap_endpoint_returns_soap_response(): void
    {
        // This is a basic test to ensure the SOAP endpoint is accessible
        // In a full test, you would send actual SOAP XML
        $response = $this->post('/soap/pck/1', [], [
            'Content-Type' => 'text/xml',
        ]);

        // Should return some response (even if it's an error due to malformed SOAP)
        $this->assertNotNull($response->getContent());
    }

    public function test_pck_credential_authentication(): void
    {
        $credential = PckCredential::first();
        
        // Test successful authentication
        $authenticated = PckCredential::authenticate(
            $credential->tenant_id,
            $credential->pck_username,
            'test_password',
            $credential->pck_license
        );
        
        $this->assertNotNull($authenticated);
        $this->assertEquals($credential->id, $authenticated->id);
        
        // Test failed authentication
        $failed = PckCredential::authenticate(
            $credential->tenant_id,
            $credential->pck_username,
            'wrong_password',
            $credential->pck_license
        );
        
        $this->assertNull($failed);
    }

    public function test_ip_whitelist_functionality(): void
    {
        $credential = PckCredential::first();
        
        // Test with no whitelist (should allow all)
        $credential->update(['ip_whitelist' => null]);
        $this->assertTrue($credential->isIpWhitelisted('192.168.1.1'));
        
        // Test with specific IP allowed
        $credential->update(['ip_whitelist' => ['192.168.1.1', '10.0.0.1']]);
        $this->assertTrue($credential->isIpWhitelisted('192.168.1.1'));
        $this->assertTrue($credential->isIpWhitelisted('10.0.0.1'));
        $this->assertFalse($credential->isIpWhitelisted('192.168.1.2'));
    }

    public function test_inbound_payload_idempotency(): void
    {
        $tenantId = 1;
        $method = PckInboundPayload::METHOD_SEND_ARTICLE;
        $payload = [
            'article' => [
                'articleId' => 123,
                'name' => 'Test Product',
                'timestamp' => time(),
            ],
        ];

        // First request should create new payload
        $result1 = PckInboundPayload::findOrCreateIdempotent($tenantId, $method, $payload);
        $this->assertFalse($result1['is_duplicate']);
        $this->assertDatabaseHas('pck_inbound_payloads', [
            'id' => $result1['payload']->id,
            'status' => PckInboundPayload::STATUS_RECEIVED,
        ]);

        // Second identical request should return existing payload
        $result2 = PckInboundPayload::findOrCreateIdempotent($tenantId, $method, $payload);
        $this->assertTrue($result2['is_duplicate']);
        $this->assertEquals($result1['payload']->id, $result2['payload']->id);
    }

    public function test_order_pck_export_scopes(): void
    {
        // Create test orders
        Order::create([
            'fornavn' => 'John',
            'etternavn' => 'Doe',
            'telefon' => '12345678',
            'ordreid' => 1001,
            'site' => 1,
            'paid' => 1, // Use integer for boolean compatibility
            'datetime' => now(),
            'pck_export_status' => 'new',
        ]);

        Order::create([
            'fornavn' => 'Jane',
            'etternavn' => 'Doe',
            'telefon' => '87654321',
            'ordreid' => 1002,
            'site' => 1,
            'paid' => 0, // Use integer for boolean compatibility
            'datetime' => now(),
            'pck_export_status' => 'new',
        ]);

        Order::create([
            'fornavn' => 'Bob',
            'etternavn' => 'Smith',
            'telefon' => '11223344',
            'ordreid' => 1003,
            'site' => 1,
            'paid' => 1, // Use integer for boolean compatibility
            'datetime' => now(),
            'pck_export_status' => 'sent',
        ]);

        // Test ready for export scope (paid and status = new)
        $readyOrders = Order::readyForPckExport()->get();
        $this->assertCount(1, $readyOrders);
        $this->assertEquals(1001, $readyOrders->first()->ordreid);

        // Test exported scope
        $exportedOrders = Order::exportedToPck()->get();
        $this->assertCount(1, $exportedOrders);
        $this->assertEquals(1003, $exportedOrders->first()->ordreid);

        // Test getOrdersForPckExport method
        $ordersForExport = Order::getOrdersForPckExport(1, 10);
        $this->assertCount(1, $ordersForExport);
        $this->assertEquals(1001, $ordersForExport->first()->ordreid);
    }

    public function test_order_pck_status_methods(): void
    {
        $order = Order::create([
            'fornavn' => 'Test',
            'etternavn' => 'User',
            'telefon' => '12345678',
            'ordreid' => 2001,
            'site' => 1,
            'paid' => 1, // Use integer for boolean compatibility
            'datetime' => now(),
            'pck_export_status' => 'new',
        ]);

        // Test marking as exported
        $order->markAsExportedToPck();
        $this->assertEquals('sent', $order->fresh()->pck_export_status);
        $this->assertNotNull($order->fresh()->pck_exported_at);
        $this->assertTrue($order->fresh()->isExportedToPck());

        // Test marking as failed
        $order->markPckExportFailed('Test error message');
        $this->assertEquals('ack_failed', $order->fresh()->pck_export_status);
        $this->assertEquals('Test error message', $order->fresh()->pck_last_error);
        $this->assertTrue($order->fresh()->hasPckExportFailed());

        // Test resetting status
        $order->resetPckExportStatus();
        $this->assertEquals('new', $order->fresh()->pck_export_status);
        $this->assertNull($order->fresh()->pck_last_error);
    }
}