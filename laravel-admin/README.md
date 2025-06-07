# Aroi Food Truck Admin System - Laravel 12

A modern Laravel 12 admin system for managing multi-location food truck orders, replacing the existing PHP admin system while maintaining compatibility with the WordPress/WooCommerce frontend.

## 🌟 Features

### Admin Dashboard
- **Multi-location Management** - Support for Namsos, Lade, Moan, Gramyra, Frosta, and Hell locations
- **Real-time Order Tracking** - Live dashboard with order statistics and status
- **Opening Hours Control** - Toggle location status (open/closed) with real-time updates
- **Order Management** - Complete CRUD operations for order processing

### Order Processing
- **Payment Tracking** - Mark orders as paid/unpaid
- **POS Integration** - Send orders to PCKasse POS system
- **SMS Notifications** - Automated customer notifications via Teletopia SMS
- **Status Management** - Track order progress from placement to completion

### API Integration
- **WordPress Compatibility** - RESTful API endpoints for WooCommerce integration
- **Legacy Support** - Maintains compatibility with existing WordPress functions
- **Real-time Processing** - Background processing of orders and payments

### Authentication & Security
- **Laravel Breeze** - Modern authentication system
- **Location-based Access** - Users only see orders for their assigned location
- **Bootstrap UI** - Modern, responsive design with Bootstrap 5

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL database (existing `admin_aroi` database)

### Installation Steps

1. **Navigate to the Laravel admin directory:**
   ```bash
   cd laravel-admin
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update `.env` file with your database credentials:**
   ```env
   APP_NAME="Aroi Admin"
   APP_ENV=production
   APP_DEBUG=false
   APP_TIMEZONE=Europe/Oslo
   APP_URL=https://yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=141.94.143.8
   DB_PORT=3306
   DB_DATABASE=admin_aroi
   DB_USERNAME=adminaroi
   DB_PASSWORD=b^754Xws
   ```

5. **Build assets:**
   ```bash
   npm run build
   ```

6. **Create symbolic link for storage:**
   ```bash
   php artisan storage:link
   ```

## 🔧 Database Configuration

The system is designed to work with your existing database structure. The models are configured to use existing tables:

- **Orders** → `orders` table
- **Users** → `users` table  
- **Opening Hours** → `apningstid` table

### User Setup
Users should have the following structure in the `users` table:
```sql
- id (int)
- username (varchar)
- password (varchar, hashed)
- siteid (int) - Location identifier (4, 5, 6, 7, 10, 11)
- license (int) - PCKasse license number
```

## 🌐 WordPress Integration

### API Endpoints

Replace the existing `https://aroiasia.no/admin/api.php` calls with:

**Base URL:** `https://yourdomain.com/laravel-admin/public/api/v1/`

#### Order Creation (from WordPress)
```php
// Replace the existing order creation in functions.php
$url = "https://yourdomain.com/laravel-admin/public/api/v1/orders";
$data = [
    'fornavn' => $fornavn,
    'etternavn' => $etternavn,
    'telefon' => $telefon,
    'ordreid' => $order_num,
    'epost' => $epost,
    'site' => $caller
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);
```

#### Mark Order as Paid
```php
// Replace the payment complete handler
$url = "https://yourdomain.com/laravel-admin/public/api/v1/orders/mark-paid";
$data = [
    'ordreid' => $order_num,
    'site' => $caller
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);
```

#### Process Orders (Cron Job)
```php
// Replace the existing api.php cron call
$url = "https://yourdomain.com/laravel-admin/public/api/v1/process-orders";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);
```

### WordPress Functions Update

Update your `functions.php` to use the new Laravel API:

```php
function your_order_details($order_id) {
    $caller = getCaller();
    $order = new WC_Order($order_id);
    
    $url = "https://yourdomain.com/laravel-admin/public/api/v1/orders";
    $data = [
        'fornavn' => $order->get_billing_first_name(),
        'etternavn' => $order->get_billing_last_name(),
        'telefon' => $order->get_billing_phone(),
        'ordreid' => $order_id,
        'epost' => $order->get_billing_email(),
        'site' => $caller
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
}

function orderIsPaid($order_id) {
    $caller = getCaller();
    
    $url = "https://yourdomain.com/laravel-admin/public/api/v1/orders/mark-paid";
    $data = [
        'ordreid' => $order_id,
        'site' => $caller
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
}
```

## 🔐 Authentication Migration

### Existing Users
The system supports the existing master password authentication (`AroMat1814`) while also supporting proper hashed passwords for new users.

### Creating New Users
```bash
php artisan tinker

# Create a new user
$user = new App\Models\User();
$user->username = 'steinkjer';
$user->password = bcrypt('secure_password');
$user->siteid = 17;
$user->license = 12345;
$user->save();
```

## 📱 Admin Features

### Dashboard
- **Real-time Statistics** - Today's orders, pending orders, unpaid orders
- **Location Status** - Toggle open/closed status with one click
- **Recent Orders** - Quick view of latest orders with action buttons
- **Opening Hours** - Display current opening hours for the location

### Order Management
- **Advanced Filtering** - Filter by status, date, customer information
- **Bulk Actions** - Mark multiple orders as paid, send to POS
- **Search Functionality** - Search by customer name, phone, order ID
- **Status Tracking** - Visual indicators for payment, processing, and completion status

### Quick Actions
- **Mark as Paid** - One-click payment confirmation
- **Send to POS** - Direct integration with PCKasse system
- **SMS Notifications** - Send order confirmations to customers
- **Status Updates** - Change order status (pending → processing → ready → complete)

## 🔧 Configuration

### Location Mapping
The system maps WordPress site IDs to location names:
- Site 4 → Lade
- Site 5 → Gramyra  
- Site 6 → Moan
- Site 7 → Namsos
- Site 10 → Frosta
- Site 11 → Hell

### POS Integration
PCKasse licenses are configured per location:
- Namsos: 6714
- Lade: 12381
- Moan: 5203
- Gramyra: 6715
- Frosta: 14780

### SMS Configuration
Uses Teletopia SMS service with credentials configured in the ApiController.

## 🚀 Deployment

### Production Setup
1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Run `composer install --optimize-autoloader --no-dev`
3. Run `npm run build`
4. Configure your web server to point to the `public` directory
5. Set up SSL certificate for secure API communication

### Web Server Configuration (Apache)
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /path/to/laravel-admin/public
    
    <Directory /path/to/laravel-admin/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
</VirtualHost>
```

### Nginx Configuration
```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /path/to/laravel-admin/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
}
```

## 🔄 Migration Steps

1. **Test Environment** - Set up Laravel system alongside existing PHP admin
2. **Update WordPress** - Gradually migrate API calls to Laravel endpoints
3. **User Training** - Train staff on new admin interface
4. **Go Live** - Switch DNS/routing to new system
5. **Monitor** - Ensure all integrations work correctly

## 📞 Support

The Laravel 12 admin system provides a modern, secure, and scalable replacement for the existing PHP admin while maintaining full compatibility with your WordPress/WooCommerce frontend.

For technical support or questions about the migration process, please refer to the Laravel documentation or contact your development team.

## 🔒 Security Notes

- All API endpoints use CSRF protection
- User authentication with session management
- Location-based access control
- Input validation and sanitization
- Secure password hashing
- SQL injection prevention through Eloquent ORM

The system is production-ready and follows Laravel best practices for security and performance.
