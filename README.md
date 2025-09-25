# AIOHM Booking Pro

**Version**: 2.0.4  
**WordPress Plugin**: Advanced Booking & Event Management System  
**License**: Freemius Premium/Free Tiers  

## Overview

AIOHM Booking Pro is a comprehensive WordPress plugin for managing event bookings and accommodation reservations. Built with a modular architecture, the plugin provides a scalable foundation for booking systems with advanced features like AI integration, payment processing, and calendar management.

## 🎯 Key Features

### Booking Systems
- **✅ Event Tickets**: Complete event management with ticket sales, pricing, and availability tracking
- **✅ Accommodations**: Room/property booking with flexible pricing and unit management  
- **✅ Calendar Integration**: Visual booking calendar with availability display
- **✅ Mixed Bookings**: Support for combined event and accommodation reservations

### Architecture Highlights
- **✅ Unified Custom Post Types**: Both events and accommodations use WordPress CPT for scalability
- **✅ Modular Design**: Feature-based modules with filesystem-enabled toggling
- **✅ Premium Integration**: Freemius licensing with tiered feature access
- **✅ Developer Friendly**: Comprehensive hooks, filters, and extensible architecture

### Premium Features 🔑
- **Stripe Payments**: Secure payment processing with webhook support
- **AI Integration**: OpenAI, Gemini, Ollama, and ShareAI modules for enhanced functionality
- **Advanced Analytics**: AI-powered booking insights and reporting
- **EventON Integration**: Import events from EventON plugin

## 🏗️ Architecture

### Core Modules
```
includes/modules/
├── booking/           # Core booking functionality
│   ├── accommodation.php    # Accommodation management
│   ├── tickets.php          # Event/ticket management  
│   ├── calendar.php         # Calendar interface
│   └── orders.php           # Order management
├── admin/             # Admin interface modules
│   ├── shortcode-admin.php  # Shortcode management
│   ├── settings.php         # Configuration
│   └── dashboard.php        # Overview dashboard
├── payments/          # Payment processing
│   └── stripe/              # Stripe integration (Premium)
├── ai/               # AI modules (All Premium)
│   ├── openai/
│   ├── gemini/
│   ├── ollama/
│   ├── shareai/
│   └── ai-analytics/
└── integrations/     # Third-party integrations
    └── eventon.php          # EventON import (Premium)
```

### Data Storage
- **Events**: `aiohm_booking_event` Custom Post Type ⭐ **NEW in v2.0.3**
- **Accommodations**: `aiohm_accommodation` Custom Post Type
- **Orders**: `aiohm_booking_order` custom table
- **Settings**: WordPress options with centralized management

## 🚀 Latest Release: v2.0.4

### Production Ready Release ⭐

**Debug Code Cleanup**: Comprehensive removal of all development debug statements for clean production deployment.

**Enhanced Order System**: Improved order creation with proper event ticket data collection and order items population.

**Data Integrity Fixes**: Resolved systematic order issues including missing email collection and pricing data.

[View Full Changelog](CHANGELOG.md)

## 📋 Installation

1. **Upload** the plugin files to `/wp-content/plugins/aiohm-booking-pro/`
2. **Activate** the plugin through WordPress admin
3. **Configure** settings via AIOHM Booking menu
4. **Add shortcodes** to pages where booking functionality is needed

## 🔧 Usage

### Shortcodes

```php
[aiohm_booking]                    // Main booking form
[aiohm_booking mode="tickets"]     // Events only
[aiohm_booking mode="accommodations"] // Accommodations only
[aiohm_booking_success]            // Success page
[aiohm_booking_checkout]           // Checkout flow
```

### Developer Hooks

```php
// Event data access (cross-module compatible)
$events = AIOHM_BOOKING_Module_Tickets::get_events_data();

// Accommodation data
$accommodations = get_posts(['post_type' => 'aiohm_accommodation']);

// Settings access
$settings = AIOHM_BOOKING_Settings::get_all();
```

## 🛠️ Development

### Requirements
- PHP 7.4+
- WordPress 5.0+
- MySQL 5.6+

### Development Setup
```bash
# Clone repository
git clone https://github.com/ohmevents/aiohm-booking-pro.git

# Install dependencies
composer install

# Enable development mode
define('AIOHM_BOOKING_DEBUG', true);
```

### Module Development
Modules are auto-discovered based on filesystem presence. Create new modules in `includes/modules/` following the abstract pattern.

## 📞 Support

- **Documentation**: [docs/](docs/)
- **Issues**: GitHub Issues
- **Premium Support**: Via Freemius dashboard

## 📄 License

This plugin uses Freemius for licensing:
- **Free Tier**: Core booking functionality
- **Premium Tier**: Advanced features (payments, AI, integrations)

---

**Developed by**: OHM Events  
**Plugin URI**: [WordPress Plugin Repository](#)  
**Current Version**: 2.0.4