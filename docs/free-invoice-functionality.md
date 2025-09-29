# AIOHM Booking Pro - Free Invoice Functionality Documentation

## Overview

AIOHM Booking Pro provides comprehensive invoice functionality for free users who don't have access to premium payment modules. This system allows businesses to handle bookings professionally by generating invoices, sending booking confirmations, and managing payment instructions via email notifications, even without integrated payment gateways.

---

## Free Invoice System Architecture

### Core Components

#### 1. **Invoice Generation Engine**
- **Client-Side Generation**: JavaScript-based invoice creation for immediate preview
- **Template System**: Professional invoice templates with company branding
- **Dynamic Content**: Real-time booking data integration
- **Print-Ready**: CSS-optimized for printing and PDF generation

#### 2. **Email Notification System**
- **Automated Emails**: Booking confirmation and invoice delivery
- **Template Management**: Customizable email templates
- **SMTP Integration**: Professional email delivery
- **Status Tracking**: Email delivery monitoring

#### 3. **Booking Management**
- **Order Creation**: Complete booking order management
- **Status Tracking**: Pending, confirmed, paid status management
- **Manual Payment Processing**: Admin tools for payment confirmation
- **Customer Communication**: Built-in communication system

---

## Invoice Generation Features

### 1. **Professional Invoice Templates**

#### Invoice Structure
```html
<div class="aiohm-invoice-header">
    <!-- Company logo and invoice number -->
    <div class="aiohm-invoice-header-left">
        <img src="company-logo.png" alt="Company Name" class="aiohm-invoice-logo">
    </div>
    <div class="aiohm-invoice-header-right">
        <div class="aiohm-invoice-number">NO. BK-2025-0001</div>
    </div>
</div>

<div class="aiohm-invoice-title">
    <h1>INVOICE</h1>
</div>

<div class="aiohm-invoice-meta">
    <div class="aiohm-invoice-date">
        <strong>Date:</strong> January 15, 2025
    </div>
</div>
```

#### Customer Information Section
```html
<div class="aiohm-invoice-parties">
    <div class="aiohm-invoice-billed-to">
        <h4>Billed to:</h4>
        <div class="aiohm-invoice-customer-info">
            <div>Customer Name</div>
            <div>customer@email.com</div>
            <div>Phone Number</div>
        </div>
    </div>
    <div class="aiohm-invoice-from">
        <h4>From:</h4>
        <div class="aiohm-invoice-company-info">
            <div>Company Name</div>
            <div>Company Address</div>
            <div>Contact Information</div>
        </div>
    </div>
</div>
```

#### Itemized Services Section
```html
<div class="aiohm-invoice-items">
    <table class="aiohm-invoice-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Event Ticket - Workshop Name</td>
                <td>2</td>
                <td>$50.00</td>
                <td>$100.00</td>
            </tr>
            <tr>
                <td>Accommodation - Room Type</td>
                <td>1</td>
                <td>$75.00</td>
                <td>$75.00</td>
            </tr>
        </tbody>
    </table>
</div>
```

#### Payment Summary
```html
<div class="aiohm-invoice-totals">
    <div class="aiohm-invoice-subtotal">
        <span>Subtotal:</span>
        <span>$175.00</span>
    </div>
    <div class="aiohm-invoice-tax">
        <span>Tax (10%):</span>
        <span>$17.50</span>
    </div>
    <div class="aiohm-invoice-total">
        <span>Total:</span>
        <span>$192.50</span>
    </div>
</div>
```

### 2. **Dynamic Data Integration**

#### JavaScript Invoice Generator
```javascript
class InvoiceGenerator {
    generateInvoicePreview() {
        const invoiceContainer = document.querySelector('#aiohm-invoice-preview');
        const bookingData = this.collectBookingData();
        const companyData = this.getCompanyData();
        const pricingData = this.calculatePricing();
        const bookingReference = this.generateBookingReference();

        // Generate professional invoice HTML
        const invoiceHtml = this.buildInvoiceTemplate({
            booking: bookingData,
            company: companyData,
            pricing: pricingData,
            reference: bookingReference,
            date: new Date().toLocaleDateString()
        });

        invoiceContainer.innerHTML = invoiceHtml;
    }

    collectBookingData() {
        return {
            contact_info: {
                name: document.querySelector('[name="buyer_name"]').value,
                email: document.querySelector('[name="buyer_email"]').value,
                phone: document.querySelector('[name="buyer_phone"]').value
            },
            booking_details: {
                events: this.getSelectedEvents(),
                accommodations: this.getSelectedAccommodations(),
                dates: this.getBookingDates()
            }
        };
    }
}
```

#### Booking Reference Generation
```javascript
generateBookingReference() {
    const year = new Date().getFullYear();
    const month = String(new Date().getMonth() + 1).padStart(2, '0');
    const timestamp = Date.now().toString().slice(-6);
    return `BK-${year}${month}-${timestamp}`;
}
```

### 3. **Company Data Integration**

#### Default Company Configuration
```javascript
getCompanyData() {
    return {
        name: 'Your Company Name',
        address: 'Company Address',
        phone: 'Phone Number',
        email: 'contact@company.com',
        website: 'www.company.com',
        logo: null, // Can be customized
        tax_id: 'Tax ID (if applicable)'
    };
}
```

---

## Email Notification System

### 1. **Invoice Email Templates**

#### Default Invoice Template
```php
// Email template for invoice notifications
$default_templates = [
    'invoice_generated' => [
        'subject' => 'Invoice for Your Booking - [booking_reference]',
        'content' => '
            <h2>Invoice for Your Booking</h2>
            <p>Dear [customer_name],</p>
            <p>Thank you for your booking. Please find your invoice details below:</p>

            <div class="booking-details">
                <h3>Booking Information</h3>
                <ul>
                    <li><strong>Booking Reference:</strong> [booking_reference]</li>
                    <li><strong>Booking Date:</strong> [booking_date]</li>
                    <li><strong>Total Amount:</strong> [total_amount]</li>
                    <li><strong>Status:</strong> [booking_status]</li>
                </ul>
            </div>

            <div class="payment-instructions">
                <h3>Payment Instructions</h3>
                <p>Please use the following details to complete your payment:</p>
                <ul>
                    <li><strong>Bank Account:</strong> [bank_details]</li>
                    <li><strong>Reference:</strong> [booking_reference]</li>
                    <li><strong>Amount:</strong> [total_amount]</li>
                </ul>
            </div>

            <p>If you have any questions, please contact us at [company_email].</p>
            <p>Best regards,<br>[company_name]</p>
        '
    ]
];
```

#### Email Variable Replacements
```php
$email_variables = [
    '[customer_name]' => $booking_data['buyer_name'],
    '[customer_email]' => $booking_data['buyer_email'],
    '[booking_reference]' => $booking_reference,
    '[booking_date]' => $booking_data['created_date'],
    '[total_amount]' => $booking_data['total_amount'],
    '[deposit_amount]' => $booking_data['deposit_amount'],
    '[booking_status]' => $booking_data['status'],
    '[company_name]' => $company_settings['name'],
    '[company_email]' => $company_settings['email'],
    '[bank_details]' => $payment_settings['bank_details']
];
```

### 2. **Email Delivery System**

#### SMTP Configuration
```php
class NotificationModule {
    public function send_invoice_notification($booking_id, $recipient = null) {
        // Load booking data
        $booking_data = $this->get_booking_data($booking_id);

        // Prepare email content
        $email_template = $this->get_email_template('invoice_generated');
        $email_content = $this->replace_variables($email_template, $booking_data);

        // Send email using WordPress mail system or SMTP
        $email_sent = wp_mail(
            $recipient ?: $booking_data['buyer_email'],
            $email_content['subject'],
            $email_content['content'],
            $this->get_email_headers()
        );

        // Log email delivery
        $this->log_email_delivery($booking_id, $email_sent);

        return $email_sent;
    }

    private function get_email_headers() {
        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('admin_email'),
            'Reply-To: ' . get_option('admin_email')
        ];
    }
}
```

### 3. **Email Template Management**

#### Customizable Templates
```php
// Admin interface for email template customization
$email_templates = [
    'booking_confirmation_user' => 'Customer booking confirmation',
    'booking_confirmation_admin' => 'Admin booking notification',
    'invoice_generated' => 'Invoice delivery email',
    'booking_approved' => 'Booking approval notification',
    'booking_cancelled_user' => 'Booking cancellation notice',
    'payment_reminder' => 'Payment reminder email'
];
```

---

## Frontend Integration

### 1. **Free User Checkout Flow**

#### Checkout Template (Free Users)
```php
<!-- Free User: Notification/Invoice -->
<div class="aiohm-booking-free-checkout">
    <div class="aiohm-booking-free-notice">
        <div class="aiohm-booking-notice-icon">📧</div>
        <h4><?php esc_html_e('Booking Confirmation', 'aiohm-booking-pro'); ?></h4>
        <p><?php esc_html_e('Your booking request has been received. You will receive a confirmation email with payment instructions.', 'aiohm-booking-pro'); ?></p>
    </div>

    <div class="aiohm-booking-invoice-preview">
        <div class="aiohm-booking-invoice-content" id="aiohm-invoice-preview">
            <!-- Invoice content will be generated here -->
        </div>
    </div>

    <div class="aiohm-booking-free-actions">
        <button type="button" class="aiohm-booking-btn aiohm-booking-btn-primary" id="aiohm-send-notification">
            <span class="aiohm-booking-btn-text"><?php esc_html_e('Send Invoice', 'aiohm-booking-pro'); ?></span>
        </button>
    </div>
</div>
```

### 2. **JavaScript Event Handling**

#### Invoice Send Functionality
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const sendInvoiceBtn = document.querySelector('#aiohm-send-notification');

    if (sendInvoiceBtn) {
        sendInvoiceBtn.addEventListener('click', function() {
            // Show processing state
            this.disabled = true;
            this.textContent = 'Sending...';

            // Collect booking data
            const bookingData = collectFormData();

            // Submit booking and send invoice
            fetch(aiohm_booking_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'aiohm_complete_manual_payment',
                    ...bookingData,
                    nonce: aiohm_booking_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Invoice sent successfully!');
                    redirectToSuccessPage();
                } else {
                    showErrorMessage(data.data.message);
                }
            })
            .catch(error => {
                showErrorMessage('Failed to send invoice');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Send Invoice';
            });
        });
    }
});
```

---

## Backend Order Management

### 1. **Order Creation**

#### Manual Payment Order Creation
```php
class CheckoutAjax {
    public static function ajax_complete_manual_payment() {
        // Verify nonce and sanitize input
        check_ajax_referer('aiohm_booking_checkout_nonce', 'nonce');

        // Create booking order
        $booking_id = $this->create_pending_order([
            'payment_method' => 'invoice',
            'status' => 'pending',
            'requires_payment' => true
        ]);

        if ($booking_id) {
            // Send invoice notification
            $notifications_module = AIOHM_BOOKING_Module_Registry::instance()->get_module('notifications');

            if ($notifications_module) {
                $invoice_sent = $notifications_module->send_invoice_notification($booking_id);

                if ($invoice_sent) {
                    wp_send_json_success([
                        'message' => 'Booking created and invoice sent',
                        'booking_id' => $booking_id
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => 'Booking saved but failed to send invoice email'
                    ]);
                }
            }
        }

        wp_send_json_error(['message' => 'Failed to create booking']);
    }
}
```

### 2. **Payment Tracking**

#### Manual Payment Confirmation
```php
class OrdersModule {
    public function mark_order_as_paid($order_id) {
        global $wpdb;

        // Update order status
        $updated = $wpdb->update(
            $wpdb->prefix . 'aiohm_booking_order',
            [
                'status' => 'paid',
                'payment_date' => current_time('mysql')
            ],
            ['id' => $order_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated) {
            // Send payment confirmation email
            $notifications_module = AIOHM_BOOKING_Module_Registry::instance()->get_module('notifications');

            if ($notifications_module) {
                $notifications_module->send_payment_confirmation($order_id);
            }

            // Trigger calendar updates
            do_action('aiohm_booking_payment_completed', $order_id);

            return true;
        }

        return false;
    }
}
```

---

## Admin Features

### 1. **Order Management Interface**

#### Free Invoice Orders View
```php
// Admin orders table with payment status
<tr class="order-row order-status-pending">
    <td><?php echo esc_html($order->id); ?></td>
    <td><?php echo esc_html($order->created_date); ?></td>
    <td><?php echo esc_html($order->buyer_name); ?></td>
    <td><?php echo esc_html($order->buyer_email); ?></td>
    <td>
        <span class="payment-method-badge invoice">
            📧 Invoice Sent
        </span>
    </td>
    <td>
        <span class="status-badge status-pending">
            Pending Payment
        </span>
    </td>
    <td class="order-actions">
        <button class="mark-paid-btn" data-order-id="<?php echo esc_attr($order->id); ?>">
            Mark as Paid
        </button>
        <button class="resend-invoice-btn" data-order-id="<?php echo esc_attr($order->id); ?>">
            Resend Invoice
        </button>
    </td>
</tr>
```

### 2. **Payment Instructions Management**

#### Configurable Payment Methods
```php
// Admin settings for payment instructions
$payment_settings = [
    'bank_transfer' => [
        'enabled' => true,
        'account_name' => 'Company Name',
        'account_number' => '1234567890',
        'bank_name' => 'Bank Name',
        'routing_number' => 'SWIFT/Routing',
        'instructions' => 'Please include booking reference in transfer description'
    ],
    'check_payment' => [
        'enabled' => false,
        'payable_to' => 'Company Name',
        'mailing_address' => 'Company Address',
        'instructions' => 'Please mail check with booking reference'
    ],
    'cash_payment' => [
        'enabled' => true,
        'location' => 'Company Office',
        'office_hours' => 'Monday-Friday 9AM-5PM',
        'instructions' => 'Cash payments accepted at our office location'
    ]
];
```

---

## Styling and Customization

### 1. **Invoice CSS Framework**

#### Professional Invoice Styling
```css
.aiohm-booking-invoice-preview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.aiohm-invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.aiohm-invoice-logo {
    max-height: 80px;
    max-width: 200px;
}

.aiohm-invoice-number {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.aiohm-invoice-title h1 {
    text-align: center;
    font-size: 36px;
    margin: 30px 0;
    color: #2c3e50;
    font-weight: 300;
}

.aiohm-invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.aiohm-invoice-table th,
.aiohm-invoice-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.aiohm-invoice-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.aiohm-invoice-totals {
    text-align: right;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #eee;
}

.aiohm-invoice-total {
    font-size: 18px;
    font-weight: bold;
    padding: 10px 0;
    border-top: 1px solid #ddd;
    margin-top: 10px;
}
```

### 2. **Print Optimization**

#### Print-Specific Styles
```css
@media print {
    .aiohm-booking-invoice-preview {
        box-shadow: none;
        border: none;
        margin: 0;
        padding: 0;
    }

    .aiohm-booking-free-actions {
        display: none;
    }

    .aiohm-invoice-header {
        page-break-inside: avoid;
    }

    .aiohm-invoice-table {
        page-break-inside: auto;
    }

    .aiohm-invoice-table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
```

---

## Security Features

### 1. **Data Protection**
- **Nonce Verification**: All AJAX requests protected with WordPress nonces
- **Input Sanitization**: Comprehensive data validation and sanitization
- **Access Control**: WordPress capability-based access restrictions
- **Email Security**: Secure email handling and delivery

### 2. **Privacy Considerations**
- **Data Minimization**: Only necessary data included in invoices
- **Secure Storage**: Customer data stored securely in WordPress database
- **GDPR Compliance**: Data handling follows privacy best practices
- **Audit Trail**: Complete logging of invoice generation and email delivery

---

## Integration Benefits

### 1. **For Free Users**
- **Professional Invoicing**: High-quality invoice generation without payment gateway costs
- **Email Automation**: Automated booking confirmations and invoice delivery
- **Order Management**: Complete booking order tracking and management
- **Customer Communication**: Built-in customer communication system

### 2. **For Business Owners**
- **Cost-Effective**: Professional booking system without payment processing fees
- **Flexible Payments**: Support for multiple payment methods (bank transfer, check, cash)
- **Manual Processing**: Admin tools for payment confirmation and order management
- **Brand Consistency**: Customizable invoice templates with company branding

### 3. **Upgrade Path**
- **Seamless Transition**: Easy upgrade to premium payment modules
- **Data Preservation**: All invoice and order data preserved during upgrade
- **Feature Enhancement**: Additional payment options available with premium upgrade
- **Backward Compatibility**: Free invoice system continues to work alongside premium features

### 4. **API Integration**
- **REST API Access**: Full invoice functionality available via API
- **Webhook Support**: Real-time notifications for invoice and booking events
- **SDK Integration**: Official SDKs support free invoice operations
- **Developer Tools**: Complete API documentation and testing tools

#### Invoice API Endpoints
- `GET /api/invoices` - Retrieve invoice data
- `POST /api/invoices/{id}/send` - Send invoice via email
- `GET /api/invoices/{id}/preview` - Generate invoice preview
- `PUT /api/bookings/{id}/mark-paid` - Mark manual payment as received

---

## Conclusion

The AIOHM Booking Pro free invoice functionality provides a comprehensive solution for businesses that need professional booking management without integrated payment processing. The system offers:

- **Professional invoice generation** with customizable templates
- **Automated email notifications** for booking confirmations and invoices
- **Complete order management** with payment tracking capabilities
- **Admin tools** for manual payment processing and customer communication
- **Responsive design** optimized for all devices and print output

This free tier functionality ensures that all users can benefit from professional booking management, regardless of their payment processing requirements, while providing a clear upgrade path to premium payment gateway integration.