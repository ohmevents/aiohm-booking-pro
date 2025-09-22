=== AIOHM Booking Pro ===
Contributors: ohm-events, aiohm, freemius
Tags: booking, events, tickets, calendar, accommodation
Requires at least: 6.2
Tested up to: 6.8
Stable tag: 2.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional event booking and accommodation management system with secure Stripe payments.

== Description ==

AIOHM Booking brings modular event ticket management to conscious venues and creators. Whether you're hosting intimate workshops, large conferences, or retreat experiences, this plugin adapts to your exact needs.

**Free Version Features:**
- Event ticket sales with customizable forms
- Professional email notifications for confirmations/cancellations
- Calendar management with date selection
- Order processing and management
- Admin interface for configuration
- Shortcode system for frontend display
- Manual/offline payment processing

**PRO Version Features:**
- Payment modules (Stripe and PayPal integration)
- AI analytics modules (ShareAI, Gemini, OpenAI, Ollama)
- Advanced payment processing
- AI-powered booking insights and analytics
- Premium support and priority updates

**Three Flexible Booking Modes:**
- **Rooms Only**: Perfect for retreats, private events, and property rentals with deposit support
- **Seats Only**: Clean ticketing system for workshops, concerts, and classes
- **Combined**: Sophisticated bookings that merge accommodation + event tickets seamlessly

**Built for Conscious Business:**
- Transparent deposit and pricing display
- Modular design that grows with your vision
- Streamlined admin experience
- Automatic booking hold management
- Professional email communications

**Perfect For:**
- Conscious venues and retreat centers
- Workshop facilitators and coaches
- Event producers and festival organizers
- Any business wanting transparent, flexible booking

== Installation ==

1. **Upload the plugin**
   - Download the plugin zip file
   - Go to WordPress Admin > Plugins > Add New
   - Click "Upload Plugin" and select the zip file
   - Click "Install Now"

2. **Activate the plugin**
   - After installation, click "Activate Plugin"
   - The plugin will automatically create required database tables

3. **Configure basic settings**
   - Go to AIOHM Booking > Settings
   - Configure currency, deposit percentage, and basic options
   - Enable desired modules (Accommodations, Tickets, etc.)

4. **Set up notifications**
   - Configure email notifications for booking confirmations
   - Customize email templates to match your brand

5. **Add booking forms to your site**
   - Use shortcodes on any page or post
   - Or use the booking widget in sidebars

== Frequently Asked Questions ==

= How do I enable different booking modes? =

Go to AIOHM Booking > Settings and enable the modules you need:
- **Accommodations**: For room/apartment bookings
- **Tickets**: For event ticketing
- **Both**: Combined accommodation + event bookings

= What payment methods are supported in the free version? =

The free version includes manual/offline payment processing. Customers receive payment instructions via email.

For automated payment processing (Stripe, PayPal), upgrade to AIOHM Booking PRO.

= Can I customize the booking forms? =

Yes! The plugin includes:
- Multiple themes (default, minimal, modern, classic)
- Shortcode attributes for customization
- Template overrides for advanced customization

= How do deposits work? =

You can set a deposit percentage (default 30%) that customers pay upfront.

- Remaining balance due upon arrival
- Transparent pricing display
- Automatic calculations

= Is there a calendar system? =

Yes! The advanced calendar system includes:
- Visual booking management
- Availability blocking
- Status indicators
- Bulk availability management

= How do I get PRO features? =

Visit our website to upgrade to AIOHM Booking PRO for:
- Stripe and PayPal payment processing
- AI-powered analytics and insights
- Premium support
- Priority updates

== Shortcodes ==

The plugin provides several shortcodes for embedding booking functionality:

### Main Booking Form
[aiohm_booking mode="auto" theme="default" show_title="true"]
- **mode**: auto (default), accommodations, tickets, both
- **theme**: default, minimal, modern, classic
- **show_title**: true (default), false
- **event_id**: Specific event ID for ticket bookings

### Checkout Page
[aiohm_booking_checkout show_summary="true"]
- **show_summary**: true (default), false
- **redirect_url**: Custom redirect after booking completion

### Events List
[aiohm_booking_events count="10" layout="list" show_dates="true"]
- **count**: Number of events to show (default: 10)
- **layout**: list (default), grid, cards
- **show_dates**: true (default), false
- **category**: Filter by event category slug

### Accommodations Only
[aiohm_booking_accommodations style="compact" show_prices="true"]
- **style**: compact (default), full, minimal
- **show_prices**: true (default), false
- **button_text**: Custom button text

== External Services ==

This plugin integrates with the following external services when their respective modules are enabled:

### Payment Processing Services

**Stripe** (PRO Feature)
- **Purpose**: Credit card and digital wallet payment processing
- **Data Sent**: Payment information (amount, currency), customer billing details, order information
- **When Data is Sent**: Only during checkout when a customer completes a payment
- **Data Storage**: No customer data is stored by the plugin; Stripe handles all payment data
- **Terms of Service**: https://stripe.com/legal
- **Privacy Policy**: https://stripe.com/privacy
- **Data Processing Agreement**: https://stripe.com/legal/dpa

**PayPal** (PRO Feature)
- **Purpose**: PayPal account and credit card payment processing
- **Data Sent**: Payment information (amount, currency), customer billing details, order information
- **When Data is Sent**: Only during checkout when a customer completes a payment
- **Data Storage**: No customer data is stored by the plugin; PayPal handles all payment data
- **Terms of Service**: https://www.paypal.com/legalhub/useragreement
- **Privacy Policy**: https://www.paypal.com/privacy
- **Developer Agreement**: https://developer.paypal.com/terms/

### Social Integration Services

**Facebook** (Optional Feature)
- **Purpose**: Social media integration for enhanced user experience
- **Data Sent**: Facebook App credentials (App ID and App Secret) for API authentication
- **When Data is Sent**: Only when configuring Facebook integration in plugin settings
- **Data Storage**: App credentials are stored securely in WordPress database using Settings API
- **Terms of Service**: https://developers.facebook.com/terms/
- **Privacy Policy**: https://developers.facebook.com/policy/
- **Platform Policy**: https://developers.facebook.com/policy/

### Data Protection & Compliance

- **GDPR Compliance**: All external service integrations comply with GDPR requirements
- **Data Minimization**: Only essential data is sent to external services
- **User Consent**: Users must explicitly enable integrations through plugin settings
- **Data Deletion**: Disabling integrations removes stored credentials
- **No Automatic Data Sharing**: The plugin never sends user data without explicit user action

For questions about data handling or privacy concerns, please contact our support team.

== Modules ==

The plugin uses a modular architecture. Enable only what you need:

### Core Modules (Free)
- **Settings**: Global configuration and preferences
- **Shortcodes**: Frontend booking forms and displays
- **Help**: Built-in documentation and support

### Booking Modules (Free)
- **Accommodations**: Room and apartment booking system
- **Tickets**: Event ticketing and seat management
- **Calendar**: Visual availability and booking management
- **Orders**: Order processing and management
- **Notifications**: Email and communication system

### PRO Modules (Upgrade Required)
- **Stripe**: Credit card and digital wallet payments
- **PayPal**: PayPal account and credit card payments
- **OpenAI**: AI-powered analytics and insights
- **Gemini**: Google's AI for booking optimization
- **Ollama**: Local AI processing
- **ShareAI**: Community AI features

== Screenshots ==

1. **Main Booking Interface** - Clean, intuitive booking form
2. **Admin Dashboard** - Comprehensive booking management
3. **Calendar System** - Visual availability management
4. **Settings Panel** - Easy configuration options
5. **Order Management** - Complete order processing
6. **Email Notifications** - Professional communication system

== Changelog ==

= 1.2.8 =
* **BUGFIX**: Fixed Stripe payment redirect issue - Payment now properly redirects to Stripe hosted checkout instead of showing success popup
* **BUGFIX**: Fixed accommodation pricing display - Corrected pricing calculation showing RON0.00, now displays actual accommodation prices
* **BUGFIX**: Fixed accommodation selection issues - Resolved problems with accommodation data extraction and selection workflow
* **ENHANCEMENT**: Improved Stripe Checkout Sessions integration - Enhanced payment flow with proper session handling and error management
* **CLEANUP**: Production-ready debug code removal - Removed all console.log statements from JavaScript files while preserving legitimate error logging
* **PERFORMANCE**: Optimized JavaScript error handling - Streamlined checkout.js, frontend.js, pricing-summary.js, and other JS files for production use
* **SECURITY**: Enhanced payment security - Improved nonce validation and data sanitization in payment processing
* **UX IMPROVEMENT**: Better payment user experience - Seamless redirect to Stripe hosted checkout with proper error handling
* **MAINTENANCE**: Code cleanup for production deployment - Eliminated debug logging while maintaining full functionality

= 1.2.7 =
* **FEATURE**: Integrated Checkout Flow - Enhanced booking experience with popup-based checkout instead of redirects for smoother user experience
* **CLEANUP**: Removed Deprecated Google Features - Eliminated Google Calendar and Google Maps integrations to simplify the plugin and focus on core booking functionality
* **ENHANCEMENT**: Streamlined Settings - Removed deprecated Google Calendar ID, Google Maps API key, and related configuration options
* **MAINTENANCE**: Code Cleanup - Removed unused Google-related JavaScript files and dependencies

= 1.2.6 =
* **ENHANCEMENT**: Calendar CSS Architecture Reorganization - Moved all calendar-specific styles from unified CSS to admin CSS for better maintainability
* **FEATURE**: Calendar Sandwich Design System - Enhanced header and footer with cohesive OHM green gradient styling and glassmorphism effects
* **UX IMPROVEMENT**: Calendar Header Layout Optimization - Reorganized filter controls and color legend on same row for better space utilization
* **ENHANCEMENT**: Calendar Visual Hierarchy - Improved spacing, typography, and responsive design patterns throughout calendar interface
* **FEATURE**: Calendar-Specific Branding - Custom `aiohm-booking-calendar-card-header` class with signature OHM green background
* **PERFORMANCE**: CSS File Size Optimization - Eliminated duplicate styles between unified and admin stylesheets (958 lines removed from unified CSS)
* **RESPONSIVE**: Enhanced Mobile Experience - Improved calendar layout with adaptive grid and flexible header controls for all screen sizes
* **ARCHITECTURE**: Modular CSS Organization - Clear separation of calendar styles enabling easier maintenance and future enhancements

= 1.2.5 =
* **BUGFIX**: Fixed critical error on settings page - Non-static method call corrected
* **ENHANCEMENT**: Improved error handling and code consistency following established patterns
* **UPDATE**: Module registry static method calls standardized across codebase
* **CLEANUP**: Prepared plugin for WordPress.org submission with proper PRO feature segregation

= 1.2.4 =
* **FEATURE**: Enhanced Module Management System - Enable/Disable Booking Modules
* **ENHANCEMENT**: Interactive toggle badges for primary booking modules (Events/Tickets and Accommodations)
* **FEATURE**: Smart module dependencies - Calendar automatically disables when Accommodations is disabled
* **FEATURE**: Dynamic admin menu - Calendar menu hidden when module is disabled
* **ENHANCEMENT**: AI Analytics module moved to PRO section with enabled status
* **FEATURE**: Selective shortcode control - Only [aiohm_booking_accommodations] disabled when Accommodations module is off
* **ENHANCEMENT**: Configure button functionality for all admin page modules
* **IMPROVEMENT**: Form customization settings persistence across page refreshes
* **IMPROVEMENT**: AJAX nonce validation compatibility for multiple form handlers
* **PRODUCTION**: Removed all debug logging from settings-related files for production readiness
* **UX IMPROVEMENT**: Visual feedback for module status changes with consistent badge styling

= 1.2.3 =
* **Version Update**: Bumped plugin version to 1.2.3
* **Bug Fixes**: Minor fixes and improvements
* **Compatibility**: Updated for latest WordPress standards

= 1.1.2 =
* **Version Update**: Bumped plugin version to 1.1.2
* **Bug Fixes**: Minor fixes and improvements
* **Compatibility**: Updated for latest WordPress standards

= 1.1.1 =
* **Environment Configuration**: Added comprehensive environment detection and configuration system
* **Enhanced Documentation**: Complete documentation overhaul with detailed API docs
* **Production Readiness**: Added automated testing and environment-specific optimizations
* **Security Improvements**: Enhanced logging and error handling
* **Performance Optimizations**: Environment-aware caching and asset management
* **Developer Experience**: Improved debugging tools and development workflow

= 1.0.0 =
* **MILESTONE RELEASE** - Complete production-ready booking system
* ✅ **Professional Code Architecture**: Complete code beautification with modular design patterns
* ✅ **Enhanced Calendar System**: Improved half-cell coloring, status management, and filtering
* ✅ **Comprehensive Help System**: Built-in support center with booking-specific troubleshooting
* ✅ **Smart Module Dependencies**: Payment modules automatically hide when accommodation is disabled
* ✅ **Production-Ready Features**: Full booking flow, order management, and admin interface
* ✅ **Security Enhancements**: SQL injection prevention and secure data handling
* ✅ **Performance Optimizations**: Cleaned debug code and optimized asset loading
* ✅ **Professional Documentation**: Complete PHPDoc comments and organized code structure

= 0.1.0 =
* Initial development version
* Basic booking functionality
* Module system foundation

== Upgrade Notice ==

= 1.2.8 =
Critical bugfix update that resolves Stripe payment redirect issues and accommodation pricing display problems. Includes production-ready code cleanup with all debug statements removed. Essential update for proper payment processing.

= 1.2.6 =
Major calendar interface update with enhanced sandwich design system, improved responsive layout, and optimized CSS architecture. Includes significant UX improvements and performance optimizations.

= 1.2.5 =
Important bugfix update that resolves a critical error on the settings page. Plugin is now ready for WordPress.org submission with proper PRO feature segregation.

= 1.2.4 =
Major feature update with enhanced module management system. Users can now enable/disable booking modules with smart dependency handling and improved admin interface.

= 1.2.3 =
Minor version update with bug fixes and compatibility improvements.

= 1.1.2 =
Minor version update with bug fixes and compatibility improvements.

= 1.1.1 =
This version includes major improvements to environment configuration and documentation. Please review the new ENVIRONMENT.md file for configuration options.

= 1.0.0 =
Major production release with significant improvements to security, performance, and user experience. Full backup recommended before upgrading.

== Support ==

For support and documentation:
- **Documentation**: Built-in help system within the plugin
- **WordPress Forums**: Community support and discussions
- **PRO Support**: Priority support available with AIOHM Booking PRO

== Contributing ==

We welcome contributions! The plugin follows WordPress coding standards and uses a modular architecture for easy extension.

== Credits ==

- **OHM Events Agency**: Plugin development and maintenance
- **WordPress Community**: Framework and ecosystem
- **Freemius**: Licensing and monetization platform (https://freemius.com)
- **Stripe**: Payment processing library (https://stripe.com)
- **Composer**: PHP dependency management

== License ==

This plugin is licensed under the GPLv2 or later.
Copyright (C) 2024 OHM Events Agency

This plugin bundles the following third-party libraries:

- **Freemius SDK**: Licensed under the Freemius SDK License
- **Stripe PHP Library**: Licensed under the MIT License