# AIOHM Booking Pro - Payment Modules Documentation

## Overview

AIOHM Booking Pro provides comprehensive payment processing capabilities through premium payment modules. These modules enable secure, professional payment handling for bookings with support for multiple payment gateways, payment methods, and advanced features like automated capture, refunds, and webhook handling.

---

## Stripe Payment Module (`stripe`)

**Access Level**: Premium only (`premium`)
**Category**: Payment (`payment`)
**Icon**: 💳

### Description
Professional payment processing with Stripe - accept credit cards, digital wallets, and international payments. Full-featured Stripe integration with support for modern payment methods and advanced features.

### Key Features

#### 1. **Multi-Environment Support**
- **Test Mode**: Complete sandbox environment for development and testing
- **Live Mode**: Production-ready payment processing
- **Easy Switching**: Toggle between test and live modes instantly
- **Separate Keys**: Independent configuration for test and live environments

#### 2. **Payment Methods Support**
- **Credit/Debit Cards**: Full support for all major card networks
- **Apple Pay**: Seamless Apple Pay integration for iOS users
- **Google Pay**: Google Pay support for Android and web users
- **Link**: Stripe's own payment method for faster checkouts
- **Digital Wallets**: Comprehensive digital wallet support

#### 3. **Advanced Payment Features**
- **Payment Intents**: Modern, secure payment processing with Strong Customer Authentication (SCA)
- **Checkout Sessions**: Hosted checkout pages for simplified integration
- **Capture Methods**:
  - **Automatic**: Immediate payment capture upon authorization
  - **Manual**: Authorize now, capture later for flexible payment timing
- **Webhook Processing**: Real-time payment status updates via secure webhooks

#### 4. **Security Features**
- **PCI Compliance**: Stripe-hosted payment forms ensure PCI compliance
- **Strong Customer Authentication (SCA)**: European regulatory compliance
- **Webhook Signature Verification**: Secure webhook endpoint protection
- **API Key Encryption**: Secure storage of sensitive credentials

### Settings Configuration

#### Environment Settings
```
stripe_test_mode (Boolean)
- Label: Test Mode
- Description: Enable test mode for development
- Default: true
```

#### API Credentials - Live Environment
```
stripe_publishable_key_live (Text)
- Label: Live Publishable Key
- Description: Your Stripe live publishable key (pk_live_...)
- Public key for frontend operations

stripe_secret_key_live (Password)
- Label: Live Secret Key
- Description: Your Stripe live secret key (sk_live_...)
- Private key for backend operations
```

#### API Credentials - Test Environment
```
stripe_publishable_key_test (Text)
- Label: Test Publishable Key
- Description: Your Stripe test publishable key (pk_test_...)
- Public key for testing

stripe_secret_key_test (Password)
- Label: Test Secret Key
- Description: Your Stripe test secret key (sk_test_...)
- Private key for testing
```

#### Webhook Configuration
```
stripe_webhook_secret (Password)
- Label: Webhook Endpoint Secret
- Description: Webhook signing secret for secure webhook verification
- Required for webhook signature validation
```

#### Payment Method Selection
```
stripe_payment_methods (Multiselect)
- Label: Enabled Payment Methods
- Options:
  - card: Credit/Debit Cards
  - apple_pay: Apple Pay
  - google_pay: Google Pay
  - link: Link
- Default: ['card']
```

#### Capture Configuration
```
stripe_capture_method (Select)
- Label: Capture Method
- Options:
  - automatic: Automatic - Capture immediately
  - manual: Manual - Authorize now, capture later
- Default: automatic
```

### Technical Implementation

#### AJAX Endpoints
- `aiohm_booking_test_stripe`: Test Stripe API connection
- `aiohm_booking_save_stripe_settings`: Save Stripe configuration
- `aiohm_booking_stripe_create_session`: Create Stripe Checkout session
- `aiohm_booking_stripe_process_payment`: Process card payments
- `aiohm_booking_stripe_webhook`: Handle Stripe webhooks

#### Frontend Assets
- **CSS**: `aiohm-booking-stripe-frontend.css` - Frontend styling
- **JavaScript**:
  - `aiohm-booking-stripe-frontend.js` - Frontend payment handling
  - `aiohm-booking-stripe-checkout.js` - Checkout session management

#### Admin Assets
- **CSS**: `aiohm-booking-stripe-admin.css` - Admin interface styling
- **JavaScript**:
  - `aiohm-booking-stripe-admin.js` - Admin functionality
  - `stripe-settings.js` - Settings page functionality

### Payment Processing Flow

#### 1. **Checkout Session Creation**
```php
// Create Stripe Checkout session
$session = Session::create([
    'payment_method_types' => ['card', 'apple_pay', 'google_pay'],
    'line_items' => $line_items,
    'mode' => 'payment',
    'success_url' => $success_url,
    'cancel_url' => $cancel_url,
    'customer_email' => $customer_email,
    'metadata' => $booking_metadata
]);
```

#### 2. **Payment Intent Processing**
```php
// Create Payment Intent for direct card processing
$intent = PaymentIntent::create([
    'amount' => $amount_cents,
    'currency' => $currency,
    'payment_method_types' => ['card'],
    'capture_method' => $capture_method,
    'metadata' => $booking_metadata
]);
```

#### 3. **Webhook Handling**
```php
// Secure webhook processing
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = Webhook::constructEvent($payload, $sig_header, $webhook_secret);

// Process webhook events
switch ($event->type) {
    case 'payment_intent.succeeded':
        $this->handle_payment_success($event->data->object);
        break;
    case 'payment_intent.payment_failed':
        $this->handle_payment_failure($event->data->object);
        break;
}
```

### Integration Features

#### 1. **WordPress Integration**
- **User Capabilities**: WordPress capability-based access control
- **Nonce Protection**: CSRF protection for all payment operations
- **Hook System**: Extensive hooks for customization
- **Settings API**: WordPress settings API integration

#### 2. **AIOHM Booking Integration**
- **Order Management**: Direct integration with booking orders
- **Status Updates**: Automatic booking status synchronization
- **Customer Management**: Customer data synchronization
- **Notification System**: Email notifications for payment events

#### 3. **Developer Features**
- **Payment Filters**: Extensive filter system for customization
- **Action Hooks**: Event-driven architecture for extensions
- **Error Handling**: Comprehensive error logging and reporting
- **Testing Tools**: Built-in connection testing and validation
- **REST API Integration**: Full payment processing via API
- **Webhook Events**: Real-time payment status notifications

#### Payment API Endpoints
- `POST /api/payments/process` - Process payment via API
- `GET /api/payments/{id}` - Retrieve payment details
- `POST /api/payments/{id}/refund` - Process payment refunds
- `GET /api/payments/methods` - List available payment methods

### Security Considerations

#### 1. **Data Protection**
- **PCI Compliance**: Stripe handles sensitive card data
- **API Key Security**: Encrypted storage of API credentials
- **HTTPS Required**: All communication over secure connections
- **Input Validation**: Comprehensive data sanitization

#### 2. **Transaction Security**
- **Strong Customer Authentication**: SCA compliance for European markets
- **3D Secure**: Built-in 3D Secure support for enhanced security
- **Fraud Detection**: Stripe's built-in fraud prevention
- **Risk Assessment**: Real-time transaction risk evaluation

### Error Handling

#### 1. **API Errors**
```php
try {
    $payment = PaymentIntent::create($payment_data);
} catch (ApiErrorException $e) {
    $this->log_error('Stripe API Error: ' . $e->getMessage());
    return $this->format_error_response($e);
}
```

#### 2. **Webhook Errors**
```php
try {
    $event = Webhook::constructEvent($payload, $sig_header, $webhook_secret);
} catch (SignatureVerificationException $e) {
    $this->log_error('Webhook signature verification failed');
    http_response_code(400);
    exit();
}
```

### Supported Features

#### 1. **Payment Operations**
- ✅ **Payment Authorization**: Hold funds for later capture
- ✅ **Payment Capture**: Capture authorized payments
- ✅ **Refunds**: Full and partial refund support
- ✅ **Voids**: Cancel authorized payments before capture
- ✅ **Customer Management**: Create and manage Stripe customers

#### 2. **Advanced Features**
- ✅ **Recurring Payments**: Subscription and recurring billing support
- ✅ **Multi-party Payments**: Split payments and marketplace support
- ✅ **International Payments**: Global payment processing
- ✅ **Multiple Currencies**: Support for 135+ currencies
- ✅ **Installment Payments**: Flexible payment plans

### Requirements

#### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **SSL Certificate**: HTTPS required for production
- **cURL**: PHP cURL extension
- **JSON**: PHP JSON extension

#### Stripe Requirements
- **Stripe Account**: Active Stripe account
- **API Keys**: Valid publishable and secret keys
- **Webhook Endpoint**: Configured webhook endpoint
- **Business Verification**: For live payments, business verification may be required

### Setup Instructions

#### 1. **Stripe Account Setup**
1. Create account at [stripe.com](https://stripe.com)
2. Complete business verification for live payments
3. Generate API keys from Stripe Dashboard
4. Configure webhook endpoints

#### 2. **Plugin Configuration**
1. Navigate to AIOHM Booking Settings → Payment Modules
2. Enable Stripe module
3. Configure API keys for test and/or live environments
4. Set up webhook endpoint and secret
5. Select enabled payment methods
6. Configure capture method preference

#### 3. **Testing Setup**
1. Enable test mode in settings
2. Use Stripe test API keys
3. Test with Stripe test card numbers
4. Verify webhook functionality
5. Test all enabled payment methods

### Best Practices

#### 1. **Security**
- Store API keys securely and rotate regularly
- Use HTTPS for all payment pages
- Implement proper error handling
- Monitor for suspicious activity
- Keep plugins and dependencies updated

#### 2. **Performance**
- Enable payment method caching
- Optimize checkout flow
- Monitor response times
- Implement proper logging
- Use Stripe's recommended practices

#### 3. **User Experience**
- Provide clear payment instructions
- Display supported payment methods
- Implement error message localization
- Test across different devices and browsers
- Provide backup payment options

---

## Payment Module Architecture

### Abstract Payment Module
All payment modules extend `AIOHM_BOOKING_Payment_Module_Abstract` providing:

- **Standardized Interface**: Consistent API across payment providers
- **Security Framework**: Built-in security and validation
- **Error Handling**: Unified error management
- **Settings Management**: Standardized configuration handling
- **Webhook Processing**: Generic webhook handling framework

### Common Features
- **Multi-environment Support**: Test and live mode configurations
- **Webhook Handling**: Secure webhook processing
- **Error Logging**: Comprehensive error tracking
- **Connection Testing**: API validation tools
- **Settings Encryption**: Secure credential storage

---

## Integration Examples

### Basic Payment Processing
```php
// Process payment using the selected payment module
$payment_result = do_action('aiohm_booking_process_payment_stripe', [
    'amount' => $booking_total,
    'currency' => $currency,
    'booking_id' => $booking_id,
    'customer_data' => $customer_info,
    'payment_method' => $payment_method
]);
```

### Custom Payment Method Registration
```php
// Register custom payment method
add_filter('aiohm_booking_payment_methods', function($methods) {
    $methods['custom_gateway'] = [
        'name' => 'Custom Gateway',
        'description' => 'Custom payment processor',
        'enabled' => true
    ];
    return $methods;
});
```

---

## Conclusion

The AIOHM Booking Pro payment modules provide enterprise-grade payment processing capabilities with comprehensive security, flexibility, and ease of use. The Stripe integration offers professional-level payment handling with support for modern payment methods, international transactions, and advanced features like automated capture and webhook processing.

The modular architecture allows for easy integration of additional payment providers while maintaining consistent security and functionality standards across all payment modules.