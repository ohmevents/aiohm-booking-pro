# Developer Documentation: Hooks & Filters

Complete reference for extending AIOHM Booking Pro with custom functionality.

## 🎣 Action Hooks

Action hooks allow you to execute custom code at specific points in the booking process.

### Booking Process Hooks

#### `aiohm_booking_before_process`
Fires before a booking is processed and saved.

```php
add_action('aiohm_booking_before_process', function($booking_data) {
    // Validate custom business rules
    if ($booking_data['guests'] > 10) {
        wp_die('Maximum 10 guests per booking');
    }

    // Log booking attempts
    error_log('New booking attempt: ' . print_r($booking_data, true));
}, 10, 1);
```

**Parameters:**
- `$booking_data` (array): Raw booking form data

#### `aiohm_booking_completed`
Fires after a successful booking is completed.

```php
add_action('aiohm_booking_completed', function($booking_id, $booking_data) {
    // Send custom notifications
    $customer_email = $booking_data['email'];
    wp_mail($customer_email, 'Welcome!', 'Thank you for your booking!');

    // Update external systems
    update_external_crm($booking_id, $booking_data);

    // Trigger marketing automation
    add_to_marketing_list($customer_email, 'booked_customer');
}, 10, 2);
```

**Parameters:**
- `$booking_id` (int): The booking/order ID
- `$booking_data` (array): Processed booking data

#### `aiohm_booking_payment_received`
Fires when payment is confirmed (Stripe/PayPal).

```php
add_action('aiohm_booking_payment_received', function($booking_id, $payment_data) {
    // Update inventory
    update_inventory_stock($booking_id);

    // Send shipping notifications
    if (booking_requires_shipping($booking_id)) {
        send_shipping_notification($booking_id);
    }

    // Award loyalty points
    $customer_id = get_booking_customer_id($booking_id);
    add_loyalty_points($customer_id, $payment_data['amount']);
}, 10, 2);
```

**Parameters:**
- `$booking_id` (int): The booking/order ID
- `$payment_data` (array): Payment gateway response data

### Email & Notification Hooks

#### `aiohm_booking_before_send_email`
Fires before any booking email is sent.

```php
add_action('aiohm_booking_before_send_email', function($email_type, $booking_id, $email_data) {
    // Add custom tracking
    if ($email_type === 'confirmation') {
        $email_data['headers'][] = 'X-Custom-Tracking: ' . generate_tracking_id();
    }

    // Log all outgoing emails
    error_log("Sending {$email_type} email for booking {$booking_id}");
}, 10, 3);
```

**Parameters:**
- `$email_type` (string): Type of email (confirmation, reminder, etc.)
- `$booking_id` (int): The booking/order ID
- `$email_data` (array): Email data array

#### `aiohm_booking_email_sent`
Fires after an email is successfully sent.

```php
add_action('aiohm_booking_email_sent', function($email_type, $booking_id, $result) {
    // Update email log
    log_email_sent($email_type, $booking_id, $result);

    // Trigger follow-up sequences
    if ($email_type === 'confirmation') {
        schedule_follow_up_email($booking_id, '+3 days');
    }
}, 10, 3);
```

**Parameters:**
- `$email_type` (string): Type of email sent
- `$booking_id` (int): The booking/order ID
- `$result` (bool): Whether email was sent successfully

## 🔍 Filter Hooks

Filter hooks allow you to modify data before it's processed or displayed.

### Booking Data Filters

#### `aiohm_booking_form_data`
Modify booking form data before validation.

```php
add_filter('aiohm_booking_form_data', function($data) {
    // Add default values
    if (empty($data['country'])) {
        $data['country'] = 'US';
    }

    // Sanitize phone numbers
    if (!empty($data['phone'])) {
        $data['phone'] = preg_replace('/[^\d]/', '', $data['phone']);
    }

    // Apply custom validation
    if ($data['guests'] < 1) {
        $data['guests'] = 1;
    }

    return $data;
});
```

**Parameters:**
- `$data` (array): Form submission data

**Returns:** Modified form data array

#### `aiohm_booking_calculate_price`
Modify calculated booking prices.

```php
add_filter('aiohm_booking_calculate_price', function($price, $booking_data) {
    // Apply bulk discounts
    $quantity = $booking_data['quantity'] ?? 1;
    if ($quantity >= 5) {
        $price *= 0.9; // 10% discount
    }

    // Add service fees
    $service_fee = $price * 0.05; // 5% service fee
    $price += $service_fee;

    // Apply tax
    $tax_rate = get_tax_rate($booking_data['location']);
    $tax = $price * $tax_rate;
    $price += $tax;

    return $price;
}, 10, 2);
```

**Parameters:**
- `$price` (float): Calculated base price
- `$booking_data` (array): Booking information

**Returns:** Modified price

#### `aiohm_booking_validate_booking`
Custom booking validation.

```php
add_filter('aiohm_booking_validate_booking', function($is_valid, $booking_data, $errors) {
    // Check age restrictions
    if ($booking_data['event_type'] === 'adult_only') {
        $age = calculate_age($booking_data['birth_date']);
        if ($age < 21) {
            $errors[] = 'This event requires attendees to be 21 or older.';
            $is_valid = false;
        }
    }

    // Check group size limits
    $max_group_size = get_event_max_group_size($booking_data['event_id']);
    if ($booking_data['guests'] > $max_group_size) {
        $errors[] = "Maximum group size is {$max_group_size} guests.";
        $is_valid = false;
    }

    return $is_valid;
}, 10, 3);
```

**Parameters:**
- `$is_valid` (bool): Current validation status
- `$booking_data` (array): Booking data being validated
- `$errors` (array): Array of error messages

**Returns:** Boolean validation result

### Display & Template Filters

#### `aiohm_booking_form_html`
Modify the booking form HTML output.

```php
add_filter('aiohm_booking_form_html', function($html, $form_data) {
    // Add custom CSS classes
    $html = str_replace('<form', '<form class="custom-booking-form"', $html);

    // Inject custom fields
    $custom_field = '<div class="custom-field">
        <label>How did you hear about us?</label>
        <select name="referral_source">
            <option>Social Media</option>
            <option>Friend</option>
            <option>Search Engine</option>
        </select>
    </div>';

    $html = str_replace('</form>', $custom_field . '</form>', $html);

    return $html;
}, 10, 2);
```

**Parameters:**
- `$html` (string): Generated form HTML
- `$form_data` (array): Form configuration data

**Returns:** Modified HTML

#### `aiohm_booking_email_template`
Customize email templates.

```php
add_filter('aiohm_booking_email_template', function($template_path, $email_type) {
    // Use custom templates
    $custom_template = get_stylesheet_directory() . "/aiohm-emails/{$email_type}.php";

    if (file_exists($custom_template)) {
        return $custom_template;
    }

    return $template_path;
}, 10, 2);
```

**Parameters:**
- `$template_path` (string): Path to email template file
- `$email_type` (string): Type of email being sent

**Returns:** Modified template path

### Settings & Configuration Filters

#### `aiohm_booking_settings`
Modify plugin settings before they're loaded.

```php
add_filter('aiohm_booking_settings', function($settings) {
    // Force specific settings
    $settings['currency'] = 'EUR';
    $settings['deposit_percentage'] = 25;

    // Add custom settings
    $settings['custom_feature_enabled'] = true;
    $settings['api_endpoint'] = 'https://api.example.com';

    return $settings;
});
```

**Parameters:**
- `$settings` (array): Plugin settings array

**Returns:** Modified settings

#### `aiohm_booking_available_payment_methods`
Modify available payment methods.

```php
add_filter('aiohm_booking_available_payment_methods', function($methods) {
    // Add custom payment method
    $methods['crypto'] = array(
        'name' => 'Cryptocurrency',
        'description' => 'Pay with Bitcoin or Ethereum',
        'icon' => 'crypto-icon.png'
    );

    // Remove PayPal for certain bookings
    if (isset($_GET['no_paypal'])) {
        unset($methods['paypal']);
    }

    return $methods;
});
```

**Parameters:**
- `$methods` (array): Available payment methods

**Returns:** Modified payment methods array

## 🔌 Extension Points

### Custom Payment Gateways

Implement custom payment processing:

```php
class Custom_Payment_Gateway extends AIOHM_BOOKING_Payment_Module_Abstract {

    public static function get_ui_definition() {
        return array(
            'id' => 'custom_gateway',
            'name' => 'Custom Payment',
            'description' => 'Process payments via custom gateway'
        );
    }

    public function process_payment($booking_data) {
        // Custom payment logic
        $result = $this->call_payment_api($booking_data);

        if ($result['success']) {
            return array(
                'success' => true,
                'transaction_id' => $result['transaction_id']
            );
        }

        return array(
            'success' => false,
            'error' => $result['error_message']
        );
    }
}
```

### Custom Field Renderers

Create custom form field types:

```php
class Custom_Date_Range_Renderer extends AIOHM_BOOKING_Field_Renderer_Abstract {

    public function render($field_config, $value = '') {
        $html = '<div class="date-range-field">';
        $html .= '<input type="date" name="' . $field_config['name'] . '_start" value="' . ($value['start'] ?? '') . '">';
        $html .= '<span>to</span>';
        $html .= '<input type="date" name="' . $field_config['name'] . '_end" value="' . ($value['end'] ?? '') . '">';
        $html .= '</div>';

        return $html;
    }

    public function validate($value, $field_config) {
        if (empty($value['start']) || empty($value['end'])) {
            return 'Both start and end dates are required.';
        }

        if (strtotime($value['start']) >= strtotime($value['end'])) {
            return 'End date must be after start date.';
        }

        return true;
    }
}
```

### Custom Booking Rules

Implement complex availability rules:

```php
add_filter('aiohm_booking_availability_rules', function($rules) {
    $rules['custom_seasonal'] = array(
        'callback' => 'check_seasonal_availability',
        'priority' => 10
    );

    return $rules;
});

function check_seasonal_availability($date, $booking_data) {
    $month = date('m', strtotime($date));

    // No bookings in December
    if ($month == '12') {
        return false;
    }

    // Higher minimum stay in summer
    if ($month >= '06' && $month <= '08') {
        return array(
            'available' => true,
            'min_stay' => 7,
            'max_stay' => 30
        );
    }

    return true;
}
```

## 📊 Database Integration

### Custom Database Tables

Create additional tables for custom functionality:

```php
register_activation_hook(__FILE__, 'create_custom_tables');

function create_custom_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'aiohm_custom_bookings';

    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        booking_id int(11) NOT NULL,
        custom_field_1 varchar(255),
        custom_field_2 text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY booking_id (booking_id)
    )";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

### Custom Post Types

Register additional content types:

```php
add_action('init', 'register_custom_post_types');

function register_custom_post_types() {
    register_post_type('aiohm_venue', array(
        'labels' => array(
            'name' => 'Venues',
            'singular_name' => 'Venue'
        ),
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_menu' => 'aiohm-booking'
    ));
}
```

## 🔐 Security Best Practices

### Data Sanitization

Always sanitize user inputs:

```php
add_filter('aiohm_booking_form_data', function($data) {
    $data['custom_field'] = sanitize_text_field($data['custom_field']);
    $data['email'] = sanitize_email($data['email']);
    $data['url'] = esc_url_raw($data['url']);

    return $data;
});
```

### Nonce Verification

Verify requests are legitimate:

```php
add_action('aiohm_booking_before_process', function($booking_data) {
    if (!wp_verify_nonce($_POST['aiohm_booking_nonce'], 'aiohm_booking_form')) {
        wp_die('Security check failed');
    }
});
```

### Permission Checks

Ensure users have appropriate permissions:

```php
add_filter('aiohm_booking_can_edit_booking', function($can_edit, $booking_id, $user_id) {
    // Allow admins to edit any booking
    if (user_can($user_id, 'manage_options')) {
        return true;
    }

    // Allow users to edit their own bookings
    $booking_user_id = get_booking_user_id($booking_id);
    if ($booking_user_id === $user_id) {
        return true;
    }

    return $can_edit;
}, 10, 3);
```

## 📈 Performance Optimization

### Caching Strategies

Cache expensive operations:

```php
add_filter('aiohm_booking_calendar_data', function($data, $month, $year) {
    $cache_key = "aiohm_calendar_{$month}_{$year}";

    $cached_data = wp_cache_get($cache_key);
    if ($cached_data !== false) {
        return $cached_data;
    }

    // Generate calendar data
    $data = generate_calendar_data($month, $year);

    // Cache for 1 hour
    wp_cache_set($cache_key, $data, '', HOUR_IN_SECONDS);

    return $data;
}, 10, 3);
```

### Database Query Optimization

Use efficient queries:

```php
add_filter('aiohm_booking_orders_query', function($query_args) {
    // Add proper indexes to query
    $query_args['meta_query'] = array(
        'relation' => 'AND',
        array(
            'key' => 'booking_status',
            'value' => 'confirmed',
            'compare' => '='
        ),
        array(
            'key' => 'event_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        )
    );

    return $query_args;
});
```

## 🧪 Testing Hooks

### Unit Testing

Test custom functionality:

```php
class Test_Custom_Booking_Logic extends WP_UnitTestCase {

    public function test_custom_price_calculation() {
        $price = 100;
        $booking_data = array('quantity' => 5);

        $modified_price = apply_filters('aiohm_booking_calculate_price', $price, $booking_data);

        $this->assertEquals(90, $modified_price); // 10% discount applied
    }

    public function test_custom_validation() {
        $booking_data = array('guests' => 15);
        $errors = array();

        $is_valid = apply_filters('aiohm_booking_validate_booking', true, $booking_data, $errors);

        $this->assertFalse($is_valid);
        $this->assertContains('Maximum 10 guests', $errors);
    }
}
```

## 📚 Additional Resources

- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [WordPress Action Reference](https://codex.wordpress.org/Plugin_API/Action_Reference)
- [WordPress Filter Reference](https://codex.wordpress.org/Plugin_API/Filter_Reference)
- [PHP Standards Recommendations](https://www.php-fig.org/psr/)

---

**Need help with custom development?** Contact our premium support team for assistance with complex integrations.