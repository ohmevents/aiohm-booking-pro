# AIOHM Booking Pro - REST API Integration Documentation

## Overview

Integrate AIOHM Booking with your existing systems using our comprehensive REST API. The API provides full access to booking management, customer data, and system functionality, enabling seamless integration with external applications, mobile apps, and third-party services.

---

## Authentication

### API Key Authentication

All API requests require authentication using API keys. API keys provide secure access to your booking data and should be treated as sensitive credentials.

#### Generating API Keys

1. Navigate to **WordPress Admin** → **AIOHM Booking** → **Settings** → **API**
2. Click **"Generate New API Key"**
3. Provide a descriptive name for the key (e.g., "Mobile App", "External CRM")
4. Set appropriate permissions for the key
5. Copy and securely store the generated API key

#### Authentication Methods

**Header Authentication (Recommended)**
```http
GET /wp-json/aiohm-booking/v1/bookings
Authorization: Bearer your_api_key_here
Content-Type: application/json
```

**Query Parameter Authentication**
```http
GET /wp-json/aiohm-booking/v1/bookings?api_key=your_api_key_here
```

#### API Key Management

```php
// Example API key structure
{
    "key_id": "abcd1234",
    "key_name": "Mobile App Integration",
    "api_key": "abk_live_1234567890abcdef",
    "permissions": ["read_bookings", "write_bookings", "read_customers"],
    "rate_limit": 1000,
    "created_date": "2025-01-15T10:30:00Z",
    "last_used": "2025-01-15T15:45:00Z",
    "status": "active"
}
```

---

## Base URL and Versioning

### API Base URL
```
https://yoursite.com/wp-json/aiohm-booking/v1/
```

### API Versioning
- **Current Version**: v1
- **Version Format**: `/wp-json/aiohm-booking/v{version}/`
- **Backward Compatibility**: Maintained for at least 12 months after new version release

---

## Available Endpoints

### 1. Bookings Management

#### **GET /api/bookings** - Retrieve Bookings
Retrieve a list of bookings with optional filtering and pagination.

**Request Parameters:**
```http
GET /wp-json/aiohm-booking/v1/bookings
Authorization: Bearer your_api_key

Query Parameters:
- page (int): Page number (default: 1)
- per_page (int): Results per page (default: 20, max: 100)
- status (string): Filter by status (pending, confirmed, paid, cancelled)
- date_from (string): Start date filter (YYYY-MM-DD)
- date_to (string): End date filter (YYYY-MM-DD)
- customer_email (string): Filter by customer email
- mode (string): Filter by booking mode (tickets, accommodation, mixed)
- order_by (string): Sort field (date, amount, status)
- order (string): Sort direction (asc, desc)
```

**Response Example:**
```json
{
    "success": true,
    "data": {
        "bookings": [
            {
                "id": 123,
                "booking_reference": "BK-202501-123456",
                "status": "confirmed",
                "mode": "tickets",
                "customer": {
                    "name": "John Doe",
                    "email": "john@example.com",
                    "phone": "+1234567890"
                },
                "booking_details": {
                    "check_in_date": "2025-02-15",
                    "check_out_date": "2025-02-17",
                    "guests_qty": 2,
                    "units_qty": 1
                },
                "pricing": {
                    "subtotal": 150.00,
                    "tax_amount": 15.00,
                    "total_amount": 165.00,
                    "deposit_amount": 50.00,
                    "currency": "USD"
                },
                "items": [
                    {
                        "item_type": "event",
                        "item_name": "Workshop: Advanced Booking",
                        "quantity": 2,
                        "unit_price": 75.00,
                        "total_price": 150.00
                    }
                ],
                "payment": {
                    "payment_method": "stripe",
                    "payment_status": "paid",
                    "payment_date": "2025-01-15T14:30:00Z",
                    "transaction_id": "pi_1234567890"
                },
                "dates": {
                    "created_date": "2025-01-15T10:00:00Z",
                    "updated_date": "2025-01-15T14:30:00Z"
                },
                "metadata": {
                    "source": "website",
                    "notes": "Special dietary requirements noted"
                }
            }
        ],
        "pagination": {
            "page": 1,
            "per_page": 20,
            "total_pages": 5,
            "total_results": 89,
            "has_next": true,
            "has_previous": false
        }
    }
}
```

#### **POST /api/bookings** - Create New Booking
Create a new booking with customer information and booking details.

**Request Body:**
```json
{
    "customer": {
        "name": "Jane Smith",
        "email": "jane@example.com",
        "phone": "+1987654321"
    },
    "booking_details": {
        "mode": "tickets",
        "check_in_date": "2025-03-01",
        "check_out_date": "2025-03-03",
        "guests_qty": 2,
        "units_qty": 1
    },
    "items": [
        {
            "item_type": "event",
            "item_id": 456,
            "quantity": 2,
            "unit_price": 85.00
        }
    ],
    "pricing": {
        "currency": "USD",
        "payment_method": "manual"
    },
    "metadata": {
        "source": "mobile_app",
        "notes": "Created via mobile application"
    }
}
```

**Response Example:**
```json
{
    "success": true,
    "data": {
        "booking_id": 124,
        "booking_reference": "BK-202501-124567",
        "status": "pending",
        "message": "Booking created successfully",
        "next_steps": {
            "payment_required": true,
            "invoice_sent": true,
            "confirmation_email": true
        }
    }
}
```

#### **PUT /api/bookings/{id}** - Update Booking
Update an existing booking's information.

**Request Body:**
```json
{
    "status": "confirmed",
    "customer": {
        "phone": "+1555123456"
    },
    "booking_details": {
        "guests_qty": 3
    },
    "metadata": {
        "notes": "Updated guest count and phone number"
    }
}
```

**Response Example:**
```json
{
    "success": true,
    "data": {
        "booking_id": 124,
        "updated_fields": ["status", "customer.phone", "booking_details.guests_qty"],
        "message": "Booking updated successfully"
    }
}
```

#### **DELETE /api/bookings/{id}** - Cancel Booking
Cancel an existing booking and process any necessary refunds.

**Request Parameters:**
```http
DELETE /wp-json/aiohm-booking/v1/bookings/124
Authorization: Bearer your_api_key

Query Parameters:
- reason (string): Cancellation reason
- refund_amount (float): Refund amount (optional)
- notify_customer (boolean): Send cancellation email (default: true)
```

**Response Example:**
```json
{
    "success": true,
    "data": {
        "booking_id": 124,
        "status": "cancelled",
        "refund_processed": true,
        "refund_amount": 50.00,
        "message": "Booking cancelled and refund processed"
    }
}
```

### 2. Customer Management

#### **GET /api/customers** - Retrieve Customers
```http
GET /wp-json/aiohm-booking/v1/customers
Authorization: Bearer your_api_key

Query Parameters:
- search (string): Search by name or email
- page (int): Page number
- per_page (int): Results per page
```

#### **POST /api/customers** - Create Customer
```json
{
    "name": "Alice Johnson",
    "email": "alice@example.com",
    "phone": "+1234567890",
    "address": {
        "street": "123 Main St",
        "city": "Anytown",
        "state": "CA",
        "zip": "12345",
        "country": "US"
    },
    "preferences": {
        "email_notifications": true,
        "sms_notifications": false
    }
}
```

### 3. Events and Accommodations

#### **GET /api/events** - Retrieve Events
```http
GET /wp-json/aiohm-booking/v1/events
Authorization: Bearer your_api_key

Query Parameters:
- status (string): published, draft, private
- date_from (string): Filter events from date
- date_to (string): Filter events to date
- category (string): Event category
- available_only (boolean): Only events with availability
```

#### **GET /api/accommodations** - Retrieve Accommodations
```http
GET /wp-json/aiohm-booking/v1/accommodations
Authorization: Bearer your_api_key

Query Parameters:
- check_in (string): Check-in date
- check_out (string): Check-out date
- guests (int): Number of guests
- available_only (boolean): Only available accommodations
```

### 4. Calendar and Availability

#### **GET /api/calendar** - Get Calendar Data
```http
GET /wp-json/aiohm-booking/v1/calendar
Authorization: Bearer your_api_key

Query Parameters:
- start_date (string): Calendar start date
- end_date (string): Calendar end date
- resource_type (string): events, accommodations, all
- resource_id (int): Specific resource ID
```

**Response Example:**
```json
{
    "success": true,
    "data": {
        "calendar_events": [
            {
                "date": "2025-02-15",
                "resource_type": "event",
                "resource_id": 456,
                "resource_name": "Workshop: Advanced Booking",
                "availability": {
                    "total_capacity": 50,
                    "booked_slots": 12,
                    "available_slots": 38
                },
                "status": "available"
            }
        ]
    }
}
```

#### **POST /api/calendar/check-availability** - Check Availability
```json
{
    "resource_type": "accommodation",
    "resource_id": 789,
    "check_in_date": "2025-03-01",
    "check_out_date": "2025-03-03",
    "guests": 2
}
```

### 5. Analytics and Reports

#### **GET /api/analytics/bookings** - Booking Analytics
```http
GET /wp-json/aiohm-booking/v1/analytics/bookings
Authorization: Bearer your_api_key

Query Parameters:
- period (string): day, week, month, year, custom
- start_date (string): For custom period
- end_date (string): For custom period
- group_by (string): status, mode, payment_method
```

#### **GET /api/analytics/revenue** - Revenue Analytics
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_revenue": 15420.50,
            "total_bookings": 89,
            "average_booking_value": 173.26,
            "growth_rate": 12.5
        },
        "breakdown": {
            "by_month": [
                {
                    "month": "2025-01",
                    "revenue": 5240.00,
                    "bookings": 32,
                    "average_value": 163.75
                }
            ],
            "by_payment_method": [
                {
                    "method": "stripe",
                    "revenue": 12420.50,
                    "percentage": 80.5
                },
                {
                    "method": "manual",
                    "revenue": 3000.00,
                    "percentage": 19.5
                }
            ]
        }
    }
}
```

---

## Rate Limits

### Rate Limiting Policy

API requests are limited to **1000 requests per hour per API key** to ensure system stability and fair usage.

#### Rate Limit Headers
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 847
X-RateLimit-Reset: 1642608000
X-RateLimit-Window: 3600
```

#### Rate Limit Exceeded Response
```json
{
    "success": false,
    "error": {
        "code": "rate_limit_exceeded",
        "message": "API rate limit exceeded. Limit: 1000 requests per hour.",
        "details": {
            "limit": 1000,
            "window": 3600,
            "reset_time": "2025-01-15T16:00:00Z"
        }
    }
}
```

#### Rate Limit Best Practices

1. **Monitor Usage**: Track rate limit headers in responses
2. **Implement Backoff**: Use exponential backoff when approaching limits
3. **Cache Responses**: Cache frequently accessed data
4. **Batch Requests**: Combine multiple operations when possible
5. **Use Webhooks**: Use webhooks for real-time updates instead of polling

---

## Error Handling

### Standard Error Response Format
```json
{
    "success": false,
    "error": {
        "code": "error_code",
        "message": "Human-readable error message",
        "details": {
            "field_errors": {
                "email": ["Email address is required"],
                "check_in_date": ["Invalid date format"]
            }
        }
    }
}
```

### Common Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `invalid_api_key` | 401 | API key is invalid or expired |
| `insufficient_permissions` | 403 | API key lacks required permissions |
| `rate_limit_exceeded` | 429 | Rate limit exceeded |
| `booking_not_found` | 404 | Booking ID does not exist |
| `validation_error` | 422 | Request data validation failed |
| `payment_error` | 400 | Payment processing error |
| `availability_error` | 409 | Resource not available |
| `server_error` | 500 | Internal server error |

---

## Webhooks

### Webhook Events

AIOHM Booking can send real-time notifications to your application when important events occur.

#### Available Events
- `booking.created` - New booking created
- `booking.updated` - Booking information updated
- `booking.cancelled` - Booking cancelled
- `payment.completed` - Payment successfully processed
- `payment.failed` - Payment processing failed
- `customer.created` - New customer registered
- `availability.changed` - Resource availability updated

#### Webhook Configuration
```php
// Configure webhook endpoints in admin panel
$webhook_config = [
    'endpoint_url' => 'https://yourapp.com/webhooks/aiohm-booking',
    'secret_key' => 'your_webhook_secret',
    'events' => ['booking.created', 'payment.completed'],
    'active' => true
];
```

#### Webhook Payload Example
```json
{
    "event": "booking.created",
    "timestamp": "2025-01-15T14:30:00Z",
    "data": {
        "booking_id": 125,
        "booking_reference": "BK-202501-125678",
        "customer": {
            "name": "Bob Wilson",
            "email": "bob@example.com"
        },
        "status": "pending",
        "total_amount": 200.00,
        "currency": "USD"
    },
    "metadata": {
        "source": "api",
        "api_key_id": "abcd1234"
    }
}
```

#### Webhook Security
```php
// Verify webhook signature
function verify_webhook_signature($payload, $signature, $secret) {
    $expected_signature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected_signature, $signature);
}

// Process webhook
if (verify_webhook_signature($payload, $_SERVER['HTTP_X_AIOHM_SIGNATURE'], $webhook_secret)) {
    $event_data = json_decode($payload, true);
    process_webhook_event($event_data);
} else {
    http_response_code(401);
    exit('Invalid signature');
}
```

---

## SDK and Client Libraries

### Official SDKs

#### PHP SDK
```php
composer require aiohm/booking-php-sdk

use AIOHM\Booking\Client;

$client = new Client('your_api_key');

// Create booking
$booking = $client->bookings()->create([
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ],
    'mode' => 'tickets',
    'items' => [...]
]);

// Get bookings
$bookings = $client->bookings()->list([
    'status' => 'confirmed',
    'per_page' => 50
]);
```

#### JavaScript SDK
```javascript
npm install @aiohm/booking-js-sdk

import { BookingClient } from '@aiohm/booking-js-sdk';

const client = new BookingClient('your_api_key');

// Create booking
const booking = await client.bookings.create({
    customer: {
        name: 'Jane Smith',
        email: 'jane@example.com'
    },
    mode: 'accommodation',
    items: [...]
});

// Get bookings with real-time updates
const bookings = await client.bookings.list({
    status: 'pending',
    realtime: true
});
```

#### Python SDK
```python
pip install aiohm-booking-python

from aiohm_booking import Client

client = Client('your_api_key')

# Create booking
booking = client.bookings.create({
    'customer': {
        'name': 'Alice Johnson',
        'email': 'alice@example.com'
    },
    'mode': 'mixed',
    'items': [...]
})

# Get analytics
analytics = client.analytics.bookings(period='month')
```

### Community SDKs
- **Ruby**: `aiohm-booking-ruby` (Community maintained)
- **Go**: `aiohm-booking-go` (Community maintained)
- **Java**: `aiohm-booking-java` (Community maintained)

---

## Integration Examples

### 1. Mobile App Integration

#### React Native Example
```javascript
import { BookingAPI } from '@aiohm/booking-js-sdk';

class BookingService {
    constructor() {
        this.api = new BookingAPI(process.env.AIOHM_API_KEY);
    }

    async createBooking(bookingData) {
        try {
            const response = await this.api.bookings.create(bookingData);
            return {
                success: true,
                booking: response.data
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }

    async getCustomerBookings(customerEmail) {
        const bookings = await this.api.bookings.list({
            customer_email: customerEmail,
            order_by: 'date',
            order: 'desc'
        });

        return bookings.data.bookings;
    }
}
```

### 2. CRM Integration

#### Salesforce Integration
```apex
public class AIOMHBookingIntegration {
    private static final String API_KEY = 'your_api_key';
    private static final String BASE_URL = 'https://yoursite.com/wp-json/aiohm-booking/v1/';

    public static void syncBookingToSalesforce(String bookingId) {
        HttpRequest req = new HttpRequest();
        req.setEndpoint(BASE_URL + 'bookings/' + bookingId);
        req.setMethod('GET');
        req.setHeader('Authorization', 'Bearer ' + API_KEY);
        req.setHeader('Content-Type', 'application/json');

        HttpResponse res = new Http().send(req);

        if (res.getStatusCode() == 200) {
            Map<String, Object> booking = (Map<String, Object>) JSON.deserializeUntyped(res.getBody());
            createSalesforceOpportunity(booking);
        }
    }
}
```

### 3. Analytics Dashboard

#### Dashboard API Integration
```javascript
class BookingDashboard {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseURL = '/wp-json/aiohm-booking/v1/';
    }

    async getDashboardData() {
        const [bookings, revenue, calendar] = await Promise.all([
            this.getBookingsAnalytics(),
            this.getRevenueAnalytics(),
            this.getCalendarData()
        ]);

        return {
            bookings,
            revenue,
            calendar,
            lastUpdated: new Date().toISOString()
        };
    }

    async getBookingsAnalytics() {
        const response = await fetch(`${this.baseURL}analytics/bookings?period=month`, {
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            }
        });

        return response.json();
    }

    async getRevenueAnalytics() {
        const response = await fetch(`${this.baseURL}analytics/revenue?period=month`, {
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json'
            }
        });

        return response.json();
    }
}
```

---

## Security Best Practices

### 1. API Key Security
- **Environment Variables**: Store API keys in environment variables
- **Key Rotation**: Regularly rotate API keys
- **Least Privilege**: Grant minimum required permissions
- **Monitoring**: Monitor API key usage and access patterns

### 2. Request Security
- **HTTPS Only**: Always use HTTPS for API requests
- **Input Validation**: Validate all input data
- **Rate Limiting**: Implement client-side rate limiting
- **Error Handling**: Don't expose sensitive information in errors

### 3. Data Protection
- **Encryption**: Encrypt sensitive data in transit and at rest
- **Access Control**: Implement proper access controls
- **Audit Logging**: Log all API access and modifications
- **Data Minimization**: Only request and store necessary data

---

## Testing and Development

### 1. Sandbox Environment

#### Test API Keys
```
Test API Key: abk_test_1234567890abcdef
Live API Key: abk_live_1234567890abcdef
```

#### Test Data
```json
{
    "test_customers": [
        {
            "name": "Test Customer",
            "email": "test@example.com",
            "phone": "+1234567890"
        }
    ],
    "test_bookings": [
        {
            "booking_reference": "BK-TEST-001",
            "status": "confirmed",
            "amount": 100.00
        }
    ]
}
```

### 2. API Testing Tools

#### Postman Collection
```json
{
    "info": {
        "name": "AIOHM Booking API",
        "description": "Complete API collection for testing"
    },
    "auth": {
        "type": "bearer",
        "bearer": {
            "token": "{{api_key}}"
        }
    },
    "variable": [
        {
            "key": "base_url",
            "value": "https://yoursite.com/wp-json/aiohm-booking/v1"
        }
    ]
}
```

#### cURL Examples
```bash
# Get all bookings
curl -X GET "https://yoursite.com/wp-json/aiohm-booking/v1/bookings" \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json"

# Create booking
curl -X POST "https://yoursite.com/wp-json/aiohm-booking/v1/bookings" \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {
      "name": "John Doe",
      "email": "john@example.com"
    },
    "mode": "tickets"
  }'
```

---

## Support and Resources

### 1. Documentation
- **API Reference**: Complete endpoint documentation
- **SDK Documentation**: Language-specific guides
- **Integration Examples**: Real-world implementation examples
- **Changelog**: API version changes and updates

### 2. Developer Support
- **Developer Portal**: Access to API keys and documentation
- **Community Forum**: Developer community and support
- **GitHub Repository**: Open-source SDKs and examples
- **Support Tickets**: Direct technical support

### 3. Monitoring and Status
- **API Status Page**: Real-time API status and uptime
- **Rate Limit Dashboard**: Monitor API usage
- **Error Analytics**: Track and analyze API errors
- **Performance Metrics**: API response time monitoring

---

## Conclusion

The AIOHM Booking Pro REST API provides comprehensive access to all booking functionality through a secure, well-documented, and developer-friendly interface. With support for multiple programming languages, real-time webhooks, and extensive integration capabilities, the API enables seamless integration with any existing system or application.

Key benefits include:

- **Complete Functionality**: Full access to all booking features via API
- **Developer-Friendly**: RESTful design with comprehensive documentation
- **Secure**: Industry-standard authentication and security practices
- **Scalable**: Rate limiting and caching for high-volume applications
- **Real-time**: Webhook support for immediate event notifications
- **Multi-language**: SDKs available for popular programming languages

Whether you're building a mobile app, integrating with a CRM, or creating custom analytics dashboards, the AIOHM Booking API provides the foundation for powerful, flexible integrations.