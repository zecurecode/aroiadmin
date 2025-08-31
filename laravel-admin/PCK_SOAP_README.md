# PCKasse SOAP Integration

This document describes the multi-tenant PCKasse SOAP integration implemented in Laravel. The system allows PCKasse POS systems to sync product data, images, and stock levels to WooCommerce stores, while also fetching orders from the webshop.

## Overview

The integration provides a SOAP web service that PCKasse can connect to for bi-directional data synchronization:

- **Inbound**: PCK sends product data, images, and stock updates (async processing with immediate ACK)
- **Outbound**: PCK fetches new orders and updates order statuses

## Architecture

### Key Components

1. **Multi-tenant System**: Each location (Steinkjer, Namsos, etc.) is a separate tenant
2. **SOAP Server**: Laravel-based SOAP server with WSDL endpoint
3. **Async Processing**: Queue-based processing for WooCommerce synchronization
4. **Idempotency**: Prevents duplicate processing of the same requests

### Database Tables

- `pck_credentials`: Authentication and configuration per tenant
- `pck_entity_maps`: Maps PCK article IDs to WooCommerce product IDs
- `pck_inbound_payloads`: Logs all inbound requests for idempotency and processing
- `orders`: Extended with PCK export status fields

## Configuration

### 1. Database Setup

Run the migrations:

```bash
cd laravel-admin
php artisan migrate
```

### 2. Seed PCK Credentials

```bash
php artisan db:seed --class=PckCredentialsSeeder
```

This creates sample credentials for all locations. **Important**: Change passwords in production!

### 3. Manage Credentials

Use the management command:

```bash
# List all credentials
php artisan pck:credentials list

# Create new credential
php artisan pck:credentials create --tenant-id=12 --username=steinkjer_pck --license=30221

# Update credential
php artisan pck:credentials update --tenant-id=12 --username=steinkjer_pck --password=new_password

# Enable/disable credential
php artisan pck:credentials enable --tenant-id=12 --username=steinkjer_pck
php artisan pck:credentials disable --tenant-id=12 --username=steinkjer_pck
```

### 4. Queue Configuration

Ensure Redis and Horizon are configured for queue processing:

```bash
# Install Redis (if not already installed)
# Configure QUEUE_CONNECTION=redis in .env

# Run queue workers
php artisan queue:work --queue=pck-inbound
# Or use Horizon for better management
php artisan horizon
```

## API Endpoints

### SOAP Endpoints

- `POST /soap/pck/{tenantKey}` - SOAP endpoint with tenant in URL
- `POST /soap/pck` - Generic SOAP endpoint (tenant resolved from credentials)
- `GET /wsdl/pck.wsdl` - WSDL file

### Management Endpoints

- `GET /pck/health` - Health check and status
- `GET /pck/tenant/{tenantKey}` - Tenant information (debug)

## SOAP Methods

### Inbound Methods (PCK → Laravel → WooCommerce)

#### `sendArticle`
Receives product data from PCK and queues for WooCommerce sync.

**Parameters:**
- `login` (int): Tenant ID
- `password` (string): PCK password
- `article` (object): Product data

**Response:** `insertUpdateResponse` with immediate ACK

#### `sendImage`
Receives product image from PCK.

**Parameters:**
- `login` (int): Tenant ID  
- `password` (string): PCK password
- `image` (base64): Image data
- `articleid` (int): PCK article ID

#### `updateStockCount`
Receives stock level updates from PCK.

**Parameters:**
- `login` (int): Tenant ID
- `password` (string): PCK password  
- `updateStock` (object): Stock data

#### `removeArticle`
Removes/hides product from webshop.

**Parameters:**
- `login` (int): Tenant ID
- `password` (string): PCK password
- `articleid` (int): PCK article ID

### Outbound Methods (Laravel → PCK)

#### `getOrders`
Returns new orders for PCK to process.

**Parameters:**
- `login` (int): Tenant ID
- `password` (string): PCK password
- `computerName` (string): PCK computer identifier

**Response:** Array of orders in PCK format

#### `updateOrderStatus`
Receives order status updates from PCK.

**Parameters:**
- `login` (int): Tenant ID
- `password` (string): PCK password
- `updateOrder` (object): Status update data

## Tenant Configuration

### Location Mapping

The system uses these tenant IDs mapped to locations:

```php
$tenantMapping = [
    'steinkjer' => 12,
    'namsos' => 7,
    'lade' => 4,
    'moan' => 6,
    'gramyra' => 5,
    'frosta' => 10,
    'hell' => 11,
];
```

### PCK License Numbers

- Namsos: 6714
- Lade: 12381
- Moan: 5203
- Gramyra: 6715
- Frosta: 14780
- Steinkjer: 30221
- Hell: N/A

## Security

### Authentication

Each tenant has:
- Username (e.g., `steinkjer_pck`)
- Encrypted password
- License number for additional validation

### IP Whitelisting

Configure allowed IP addresses per tenant:

```bash
php artisan pck:credentials update --tenant-id=12 --username=steinkjer_pck --ip-whitelist=192.168.1.100,10.0.0.50
```

### Rate Limiting

The system includes rate limiting on SOAP endpoints to prevent abuse.

## Data Flow

### Product Sync (PCK → WooCommerce)

1. PCK sends `sendArticle` SOAP request
2. Laravel immediately returns OK response
3. Request is queued for async processing
4. Queue job processes article data:
   - Normalizes PCK data to WooCommerce format
   - Creates/updates product via WooCommerce REST API
   - Updates entity mapping for future reference

### Order Sync (WooCommerce → PCK)

1. PCK calls `getOrders` SOAP method
2. Laravel returns new/unpaid orders immediately
3. Orders are marked as exported to prevent duplicates
4. PCK processes orders and calls `updateOrderStatus` with results

## Queue Jobs

### `ProcessInboundArticlePayload`
- Processes product data from PCK
- Creates/updates WooCommerce products
- Handles product attributes, pricing, stock

### `ProcessInboundImagePayload`  
- Uploads images to WordPress media library
- Attaches images to WooCommerce products
- Handles company logos (articleId = -10)

### `ProcessStockUpdatePayload`
- Updates product stock levels in WooCommerce
- Handles both simple products and variations
- Updates stock status based on quantity

## Error Handling

### SOAP Faults
Returns appropriate SOAP faults for:
- Authentication failures
- Invalid parameters
- System errors

### Retry Logic
Failed queue jobs are automatically retried with exponential backoff.

### Logging
All operations are logged with appropriate context for debugging.

## Monitoring

### Health Check
```bash
curl http://your-domain.com/pck/health
```

Returns system status including:
- SOAP extension availability
- Database connectivity
- Number of enabled tenants
- WSDL file accessibility

### Queue Monitoring
Use Laravel Horizon for queue monitoring:
```bash
php artisan horizon:dashboard
```

## Testing

Run the test suite:
```bash
php artisan test tests/Feature/PckSoapTest.php
```

## Troubleshooting

### Common Issues

1. **SOAP Extension Not Loaded**
   ```bash
   # Install PHP SOAP extension
   sudo apt-get install php-soap
   # Or for other systems:
   # sudo yum install php-soap
   ```

2. **Queue Jobs Not Processing**
   ```bash
   # Check queue worker is running
   php artisan queue:work --queue=pck-inbound
   
   # Check failed jobs
   php artisan queue:failed
   ```

3. **Authentication Failures**
   ```bash
   # Verify credentials
   php artisan pck:credentials list
   
   # Check tenant configuration
   curl http://your-domain.com/pck/tenant/12
   ```

4. **WooCommerce API Errors**
   - Verify WooCommerce REST API keys in `_avdeling` table
   - Check SSL certificate validity
   - Confirm API endpoint accessibility

### Debug Mode

Enable debug logging in `.env`:
```bash
LOG_LEVEL=debug
```

## Production Deployment

### Security Checklist

- [ ] Change all default PCK passwords
- [ ] Configure IP whitelisting for production PCK systems
- [ ] Enable SSL/HTTPS for all endpoints
- [ ] Set up proper firewall rules
- [ ] Configure log rotation
- [ ] Set up monitoring and alerting

### Performance Optimization

- [ ] Configure Redis for queues
- [ ] Set up Horizon for queue monitoring
- [ ] Enable opcache for PHP
- [ ] Configure proper database indexes
- [ ] Set up CDN for static assets

### Backup Strategy

- [ ] Regular database backups including credentials
- [ ] Queue job state backup
- [ ] Entity mapping backup for disaster recovery

## Support

For issues or questions:

1. Check logs in `storage/logs/laravel.log`
2. Use health check endpoint for system status
3. Review queue status in Horizon dashboard
4. Verify tenant configuration with management commands