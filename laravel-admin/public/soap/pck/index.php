<?php
/**
 * Pure PHP SOAP Server for PCKasse - No Laravel wrapper
 * This ensures proper SOAP response format without Laravel interference
 */

// Bootstrap Laravel minimally
require_once __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Clean all output
while (ob_get_level() > 0) { 
    ob_end_clean(); 
}

// No HTML output before SOAP
ini_set('display_errors', '0');
ini_set('soap.wsdl_cache_enabled', '0');

// Get tenant key from URL
$tenantKey = $_GET['tenant'] ?? basename(dirname($_SERVER['REQUEST_URI'])) ?? null;

try {
    // SoapServer in pure WSDL mode
    $wsdl = __DIR__ . '/../../wsdl/pck.wsdl';
    $server = new SoapServer($wsdl, [
        'soap_version' => SOAP_1_1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    ]);

    // Create handler
    $handler = new \App\Soap\PckSoapHandler(
        \Illuminate\Http\Request::createFromGlobals(), 
        $tenantKey
    );
    
    $server->setObject($handler);
    
    // Let SoapServer handle everything
    $server->handle();

} catch (Throwable $e) {
    // Return SOAP fault
    $fault = new SoapFault('Server', 'Internal error: ' . $e->getMessage());
    header('Content-Type: text/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>' .
         '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" ' .
         'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
         'xmlns:xsd="http://www.w3.org/2001/XMLSchema">' .
         '<soap:Body>' .
         '<soap:Fault>' .
         '<faultcode>soap:Server</faultcode>' .
         '<faultstring>' . htmlspecialchars($e->getMessage()) . '</faultstring>' .
         '<detail />' .
         '</soap:Fault>' .
         '</soap:Body>' .
         '</soap:Envelope>';
}