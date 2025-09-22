# User Guide: Setting Up Events & Tickets

This guide walks you through creating and managing event tickets with AIOHM Booking Pro.

## 🎫 Creating Your First Event

### Step 1: Access Events
1. Go to **AIOHM Booking > Events** in your WordPress admin
2. Click **Add New Event**

### Step 2: Basic Event Information
Fill in the essential details:

- **Event Title**: Name of your event (e.g., "Mindfulness Workshop")
- **Event Description**: Detailed description of what attendees can expect
- **Event Date & Time**: When the event takes place
- **Event Location**: Physical address or "Virtual Event"
- **Organizer**: Your name or organization
- **Event Image**: Upload an attractive banner image

### Step 3: Ticket Configuration
Set up your ticket pricing and availability:

- **Ticket Price**: Base price per ticket
- **Maximum Capacity**: Total number of tickets available
- **Minimum Tickets**: Minimum tickets per booking (usually 1)
- **Maximum Tickets**: Maximum tickets per booking (prevent bulk purchases)

### Step 4: Advanced Settings
Configure additional options:

- **Early Bird Pricing**: Set discounted prices for early bookings
- **Ticket Types**: Create VIP, Regular, Student pricing tiers
- **Booking Deadline**: When ticket sales close
- **Refund Policy**: Your cancellation terms

## 🎟️ Ticket Types & Pricing

### Multiple Ticket Tiers
Create different ticket options:

1. **Early Bird**: $50 (available until 30 days before event)
2. **Regular**: $75 (standard pricing)
3. **VIP**: $125 (includes premium perks)
4. **Student**: $40 (with valid student ID)

### Group Discounts
Set up automatic discounts:
- 3+ tickets: 10% off
- 5+ tickets: 15% off
- 10+ tickets: 20% off

## 📅 Event Scheduling

### Single Events
Perfect for one-time workshops, concerts, or classes.

### Recurring Events
For ongoing classes or series:
- Weekly yoga classes
- Monthly meetups
- Seasonal workshops

### Multi-Date Events
Events spanning multiple days:
- 3-day retreats
- Week-long conferences
- Festival weekends

## 🗓️ Calendar Management

### Setting Availability
1. Go to **AIOHM Booking > Calendar**
2. Select dates when tickets are available
3. Block dates for holidays or maintenance

### Bulk Operations
- **Block Entire Month**: Click month header
- **Block Date Range**: Click and drag across dates
- **Set Recurring Blocks**: Weekly off-days, holidays

## 💰 Payment Processing

### Free Version (Manual Payment)
- Customers receive payment instructions via email
- You manually confirm payments and mark orders complete
- Perfect for small operations or in-person payments

### Pro Version (Automated Payment)
- **Stripe Integration**: Secure credit card processing
- **PayPal**: PayPal account and card payments
- **Instant Confirmation**: Automatic payment verification
- **Failed Payment Handling**: Automatic retry logic

## 📧 Email Communications

### Automatic Emails
The system sends professional emails for:

- **Booking Confirmation**: Immediately after successful booking
- **Payment Instructions**: For manual payment methods
- **Event Reminders**: 24 hours before event (optional)
- **Cancellation Notice**: When bookings are cancelled

### Customizing Email Templates
1. Go to **AIOHM Booking > Settings > Notifications**
2. Edit email subject lines and content
3. Add your branding and contact information
4. Include event-specific details

## 📊 Managing Event Bookings

### Viewing Orders
1. Go to **AIOHM Booking > Orders**
2. Filter by event, date, or status
3. View customer details and booking information

### Order Statuses
- **Pending**: Awaiting payment confirmation
- **Confirmed**: Payment received, booking confirmed
- **Cancelled**: Booking cancelled (refund processed if applicable)
- **Completed**: Event has passed

### Exporting Data
- Export booking lists to CSV
- Generate reports for accounting
- Customer data for email marketing

## 🎨 Customizing Event Pages

### Shortcode Options
Display events with different layouts:

```php
[aiohm_booking_events layout="grid" show_dates="true"]
[aiohm_booking_events category="workshops" count="6"]
[aiohm_booking event_id="123"] // Specific event booking form
```

### Theme Integration
Events automatically inherit your WordPress theme styling, or use built-in themes:
- Default, Minimal, Modern, Classic

## 📈 Analytics & Reporting

### Basic Reports
- Total tickets sold
- Revenue by event
- Popular ticket types
- Booking trends over time

### Pro Analytics (Coming Soon)
- Customer demographics
- Conversion funnel analysis
- Marketing campaign tracking
- Automated reporting

## 🔧 Advanced Configuration

### Custom Fields
Add custom information collection:
- Dietary restrictions
- Accessibility needs
- Emergency contact information
- Special requests

### Integration Options
- **Mailchimp**: Automatic subscriber addition
- **Zapier**: Connect to 2,000+ apps
- **Google Calendar**: Sync event dates
- **Zoom**: Automatic meeting creation

## 🚨 Handling Cancellations

### Customer Cancellations
1. Customers can cancel through their booking confirmation email
2. Automatic refund processing (if enabled)
3. Ticket re-release for other customers

### Your Cancellations
If you need to cancel an event:
1. Mark event as cancelled in admin
2. Automatic refund processing
3. Customer notification emails
4. Option to offer alternative dates

## 💡 Best Practices

### Event Creation
- **Clear Descriptions**: Include what's included, duration, and what to bring
- **Professional Images**: High-quality photos increase bookings
- **Detailed Pricing**: Explain what's included in each ticket type
- **Capacity Planning**: Don't oversell - consider no-shows

### Customer Communication
- **Prompt Responses**: Reply to inquiries within 24 hours
- **Clear Policies**: Refund, cancellation, and attendance policies
- **Regular Updates**: Keep customers informed of changes
- **Post-Event Follow-up**: Request feedback and announce future events

### Technical Tips
- **Test Bookings**: Always test your event pages before promoting
- **Mobile Optimization**: Ensure booking forms work on mobile devices
- **Backup Data**: Regularly export booking data
- **Monitor Capacity**: Update availability as bookings come in

## 🆘 Troubleshooting

### Common Issues

**"Event Not Available" Error:**
- Check calendar availability settings
- Verify event hasn't reached capacity
- Confirm event date hasn't passed

**Payment Processing Issues:**
- Verify Stripe/PayPal API keys
- Check for expired cards
- Review payment gateway error logs

**Email Delivery Problems:**
- Check spam/junk folders
- Verify SMTP settings
- Test with different email providers

**Calendar Display Issues:**
- Clear browser cache
- Check for JavaScript conflicts
- Verify theme compatibility

---

**Need more help?** Check our [full documentation](https://docs.ohm.events) or contact support.