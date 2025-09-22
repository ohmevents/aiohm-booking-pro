# Frequently Asked Questions (FAQ)

Comprehensive answers to common questions about AIOHM Booking Pro.

## 🚀 Getting Started

### What is AIOHM Booking Pro?

AIOHM Booking Pro is a comprehensive WordPress plugin for managing event bookings and accommodation reservations. It provides a complete booking system with payment processing, calendar management, and automated communications.

### How do I install the plugin?

1. Download the plugin zip file
2. Go to **WordPress Admin > Plugins > Add New**
3. Click **Upload Plugin** and select the zip file
4. Click **Install Now** then **Activate Plugin**
5. Complete the setup wizard

### What's the difference between free and pro versions?

**Free Version:**
- Manual payment processing
- Basic booking forms
- Email notifications
- Calendar management

**Pro Version:**
- Stripe & PayPal integration
- AI analytics (if enabled)
- Advanced customization
- Premium support

### Do I need coding skills to use this plugin?

No! The plugin is designed for users of all technical levels:
- Simple setup wizard
- Visual drag-and-drop calendar
- Pre-built form templates
- Comprehensive documentation

## 💰 Pricing & Payments

### What payment methods are supported?

**Free Version:**
- Manual payments (bank transfer, check, cash)
- Payment instructions sent via email

**Pro Version:**
- **Stripe**: Credit cards, digital wallets (Apple Pay, Google Pay)
- **PayPal**: PayPal accounts and credit cards
- **Future**: Additional gateways via extensions

### How do deposits work?

Deposits are a percentage of the total booking cost paid upfront:
- Default: 30% deposit required
- Configurable in settings (0-100%)
- Balance due upon arrival
- Protects both you and your customers

### Can customers pay the full amount upfront?

Yes! You can set the deposit to 100% for full payment upfront, or 0% for payment upon arrival.

### What currencies are supported?

The plugin supports all currencies that your payment processor supports:
- Stripe: 135+ currencies
- PayPal: 25+ major currencies
- Currency selection in plugin settings

### Are there transaction fees?

Transaction fees depend on your payment processor:
- **Stripe**: 2.9% + $0.30 per transaction (USD)
- **PayPal**: 2.9% + $0.49 per transaction (USD)
- No additional fees from AIOHM Booking Pro

## 📅 Calendar & Availability

### How does the calendar system work?

The calendar visually manages availability:
- **Green**: Available dates
- **Red**: Booked/unavailable dates
- **Yellow**: Partially booked
- Click dates to toggle availability
- Bulk operations for efficiency

### Can I block dates in advance?

Yes! Block dates for:
- Maintenance and cleaning
- Personal use
- Holiday closures
- Special events

### How do I set different availability for different properties?

Each accommodation can have its own calendar:
1. Select property from calendar dropdown
2. Set availability specific to that property
3. Different rules for different units

### Can customers book partial days?

Currently, the system works with full-day bookings. Partial day bookings (morning/afternoon) are planned for future updates.

## 🎫 Events & Tickets

### How many ticket types can I create?

Unlimited! Create as many ticket types as needed:
- Early Bird (discounted)
- Regular pricing
- VIP (premium)
- Student discounts
- Group rates

### Can I set minimum/maximum ticket purchases?

Yes! Configure per event:
- **Minimum**: Force multi-ticket purchases (e.g., minimum 2 tickets)
- **Maximum**: Prevent bulk buying (e.g., maximum 10 tickets per person)

### How do I handle sold-out events?

The system automatically:
- Stops accepting bookings when capacity reached
- Shows "Sold Out" message on booking forms
- Sends waitlist notifications (future feature)

### Can I edit events after creation?

Yes! Edit any aspect of events:
- Change dates, prices, descriptions
- Modify capacity and ticket types
- Update images and content
- Existing bookings are preserved

## 🏠 Accommodation Bookings

### What's the difference between rooms and properties?

- **Rooms**: Individual rooms in a larger property (B&B, hostel)
- **Properties**: Entire homes, cabins, apartments
- **Both**: Mix room and property bookings

### How do I handle cleaning fees?

Add cleaning fees as:
- Fixed amount per booking
- Percentage of booking cost
- Separate line item in checkout

### Can guests book multiple rooms?

Yes! The system supports:
- Single room bookings
- Multiple room bookings
- Entire property bookings
- Mixed room + property combinations

### How far in advance can bookings be made?

Configurable in settings:
- **Minimum**: Same day bookings allowed
- **Maximum**: Book up to 2 years in advance
- Different rules per property type

## 📧 Emails & Notifications

### What emails are sent automatically?

- **Booking Confirmation**: Immediate confirmation with details
- **Payment Instructions**: For manual payment methods
- **Event Reminders**: Configurable (24 hours before, etc.)
- **Cancellation Notices**: When bookings are cancelled
- **Admin Notifications**: New booking alerts

### Can I customize email templates?

Yes! Fully customizable:
- Subject lines and content
- HTML templates with branding
- Dynamic content (customer names, booking details)
- Multiple language support

### Do emails go to spam?

To prevent spam issues:
- Use recognizable sender addresses
- Include unsubscribe links
- Avoid excessive special characters
- Set up proper SPF/DKIM records

### Can I send emails in different languages?

Yes! The plugin supports:
- WordPress multilingual plugins (WPML, Polylang)
- Custom language files
- Per-customer language preferences

## 🔧 Customization

### Can I change the booking form design?

Yes! Multiple customization options:
- **Themes**: Default, Minimal, Modern, Classic
- **Colors**: Match your brand colors
- **Layout**: Compact or full-width forms
- **Custom CSS**: Advanced styling

### How do I add custom fields?

Add custom fields for additional information:
- Text fields, dropdowns, checkboxes
- Required or optional fields
- Different fields per booking type
- Custom validation rules

### Can I integrate with my existing website?

Yes! Seamless integration:
- Works with all WordPress themes
- Shortcode system for placement
- Widget support for sidebars
- API for custom integrations

### Is the plugin mobile-friendly?

Yes! Fully responsive design:
- Mobile-optimized booking forms
- Touch-friendly calendars
- Mobile payment processing
- Responsive email templates

## 🔒 Security & Privacy

### Is customer data secure?

Yes! Multiple security measures:
- SSL encryption required for payments
- Data sanitization and validation
- Secure database storage
- Regular security updates

### What personal data is collected?

Standard booking information:
- Name, email, phone
- Billing/shipping addresses
- Payment information (processed securely)
- Custom fields (as configured)

### GDPR compliance?

The plugin is GDPR-ready:
- Data export functionality
- Right to erasure (delete data)
- Consent management
- Privacy policy integration

### Can I limit who can book?

Yes! Access controls:
- Require user accounts
- Age verification
- Geographic restrictions
- Custom approval processes

## 📊 Reports & Analytics

### What reports are available?

**Free Version:**
- Basic booking lists
- Revenue summaries
- Customer information

**Pro Version:**
- Advanced analytics
- Conversion tracking
- Marketing campaign reports
- Automated reporting

### Can I export booking data?

Yes! Export options:
- CSV format for spreadsheets
- Excel compatibility
- Date range filtering
- Custom field inclusion

### How do I track revenue?

Comprehensive financial tracking:
- Total revenue by period
- Revenue by booking type
- Payment method breakdown
- Outstanding balance reports

## 🆘 Troubleshooting

### Why isn't the booking form showing?

Common causes:
- Shortcode syntax error: `[aiohm_booking]` (correct)
- Plugin not activated
- Theme/plugin conflicts
- JavaScript disabled

### Why are payments failing?

Payment issues:
- Invalid API keys
- SSL certificate missing
- Card declined by bank
- Geographic restrictions

### Why aren't emails being sent?

Email problems:
- SMTP not configured
- Emails going to spam
- Wrong sender address
- Hosting provider blocks

### Why is the calendar not loading?

Calendar issues:
- Browser cache problems
- JavaScript conflicts
- Theme compatibility
- Server resource limits

## 🚀 Advanced Features

### Can I create recurring events?

Yes! Support for:
- Weekly classes
- Monthly meetings
- Seasonal events
- Custom recurrence patterns

### Is there a waitlist feature?

Waitlist functionality (planned):
- Automatic waitlist management
- Notifications when spots open
- Priority booking for waitlisted customers

### Can I offer discounts?

Multiple discount options:
- Percentage discounts
- Fixed amount off
- Early bird pricing
- Loyalty program discounts

### API access available?

REST API endpoints available:
- Booking creation and management
- Calendar availability
- Customer data access
- Webhook integrations

## 💼 Business Features

### Can I manage multiple locations?

Yes! Multi-location support:
- Different calendars per location
- Location-specific pricing
- Regional availability rules
- Location-based tax calculation

### Is there inventory management?

Basic inventory tracking:
- Ticket/capacity management
- Resource allocation
- Overbooking prevention
- Real-time availability updates

### Can I set up automated reminders?

Yes! Automated communications:
- Pre-event reminders
- Payment due notices
- Follow-up emails
- Marketing sequences

### Tax calculation support?

Tax features:
- Automatic tax calculation
- Tax-inclusive/exclusive pricing
- Location-based tax rates
- Tax reporting for accounting

## 🔄 Updates & Support

### How do I update the plugin?

Automatic updates:
- WordPress notifies of updates
- One-click updates available
- Backup before updating
- Test updates on staging site first

### What's included in premium support?

Premium support includes:
- Priority email support
- Live chat assistance
- Phone support for complex issues
- Custom development assistance
- Training and onboarding

### Can I transfer my license?

License transfers allowed:
- One-time transfer per license
- Contact support for assistance
- Transfer to new domain/email
- Documentation required

### Refund policy?

Refund policy:
- 30-day money-back guarantee
- Full refund for unused licenses
- Partial refunds for technical issues
- No refunds after 30 days

---

**Still have questions?** Check our [documentation](https://docs.ohm.events) or contact support.