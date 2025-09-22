# AIOHM Booking Pro Documentation

Welcome to the comprehensive documentation for AIOHM Booking Pro. This documentation is organized to help both end users and developers get the most out of the plugin.

## 📚 Documentation Overview

### For End Users
- **[Getting Started](getting-started.md)** - Quick setup guide and basic usage
- **[Events & Tickets Guide](events-tickets-guide.md)** - Complete guide to setting up event bookings
- **[Accommodation Guide](accommodation-guide.md)** - Managing property and room bookings
- **[FAQ](faq.md)** - Answers to common questions
- **[Troubleshooting](troubleshooting.md)** - Solutions to common issues

### For Developers
- **[Developer Hooks & Filters](developer-hooks-filters.md)** - Complete API reference for extending the plugin

### Additional Resources
- **[README.md](../README.md)** - Technical overview, installation, and API docs
- **[CHANGELOG.md](../CHANGELOG.md)** - Version history and updates
- **[WordPress Plugin Directory](https://wordpress.org/plugins/aiohm-booking/)** - Official plugin page

## 🚀 Quick Start

New to AIOHM Booking Pro? Start here:

1. **[Install the plugin](../README.md#installation)**
2. **[Complete basic setup](getting-started.md)**
3. **[Create your first event](events-tickets-guide.md)** or **[accommodation](accommodation-guide.md)**
4. **[Add booking forms to your site](getting-started.md#step-4-add-booking-forms-to-your-site)**

## 🎯 Common Tasks

### Setting Up Bookings
- [Create event tickets](events-tickets-guide.md#creating-your-first-event)
- [Set up accommodation bookings](accommodation-guide.md#creating-accommodation-listings)
- [Configure payment processing](../README.md#payment-processing)
- [Customize booking forms](../README.md#customization)

### Managing Your Business
- [View and manage bookings](../README.md#managing-bookings)
- [Handle calendar availability](events-tickets-guide.md#calendar-management)
- [Process payments and refunds](../README.md#payment-processing)
- [Send customer communications](../README.md#email-communications)

### Customization & Extensions
- [Customize form appearance](../README.md#customization)
- [Add custom fields](../README.md#custom-fields)
- [Extend with hooks & filters](developer-hooks-filters.md)
- [Integrate with other systems](developer-hooks-filters.md#database-integration)

## 🆘 Getting Help

### Self-Service
- **[FAQ](faq.md)** - Search for answers to common questions
- **[Troubleshooting](troubleshooting.md)** - Step-by-step solutions to problems
- **Search Documentation** - Use your browser's search (Ctrl+F / Cmd+F)

### Premium Support
For Pro license holders:
- **Email Support**: [support@ohm.events](mailto:support@ohm.events)
- **Priority Response**: 24-48 hour response time
- **Live Chat**: Available during business hours
- **Phone Support**: For complex technical issues

### Community Resources
- **WordPress.org Forums**: Community discussions and user tips
- **GitHub Issues**: Bug reports and feature requests
- **Video Tutorials**: Step-by-step walkthroughs (coming soon)

## 📖 Documentation Conventions

### Icons Used
- 🚀 **Getting Started** - Beginner-friendly guides
- ⚙️ **Configuration** - Settings and setup
- 🎨 **Customization** - Design and branding
- 🔧 **Technical** - Developer-focused content
- 🆘 **Help** - Troubleshooting and support
- 💡 **Tips** - Best practices and recommendations

### Code Examples
```php
// PHP code examples
add_filter('aiohm_booking_settings', function($settings) {
    $settings['currency'] = 'EUR';
    return $settings;
});
```

```javascript
// JavaScript examples
jQuery(document).ready(function($) {
    $('.booking-form').addClass('custom-styling');
});
```

```bash
# Command line examples
wp plugin update aiohm-booking-pro
```

### Shortcode Examples
```php
[aiohm_booking mode="tickets" event_id="123"]
[aiohm_booking_checkout show_summary="true"]
[aiohm_booking_accommodations style="compact"]
```

## 🔄 Version Information

This documentation is for **AIOHM Booking Pro v2.0.0**.

- **Last Updated**: September 2025
- **WordPress Compatibility**: 6.2+
- **PHP Compatibility**: 7.4+

For older versions, check the [changelog](../CHANGELOG.md) or contact support.

## 🤝 Contributing to Documentation

We welcome contributions to improve our documentation:

1. **Report Issues**: Found unclear or missing information? [Let us know](https://github.com/ohm-events/aiohm-booking-pro/issues)
2. **Suggest Improvements**: Have ideas for better documentation? [Share them](https://github.com/ohm-events/aiohm-booking-pro/discussions)
3. **Contribute Content**: Help write or improve guides (contact us for contributor access)

## 📄 License

This documentation is licensed under the same terms as AIOHM Booking Pro (GPLv2 or later).

---

**Built for conscious businesses by [OHM Events Agency](https://www.ohm.events)**

*Making booking management simple, beautiful, and effective.*