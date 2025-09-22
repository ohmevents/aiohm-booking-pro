# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing & Quality Assurance
```bash
# Run PHP unit tests (if available)
composer test

# Run tests with coverage (if available)
composer test:coverage

# Run WordPress integration tests (if available)
composer test:integration
```

### Development Server
```bash
# Start development server (if npm scripts exist)
npm run dev
```

## Project Architecture

### Plugin Overview
AIOHM Booking Pro is a WordPress plugin for event booking and accommodation management. The plugin follows a modular architecture with a central module registry system that dynamically loads features based on file system presence.

### Core Architecture Components

#### Main Plugin Entry Point
- **aiohm-booking-pro.php**: Main plugin file with Freemius integration, initialization hooks, and database table management

#### Module System Architecture
- **Module Registry**: `includes/core/class-aiohm-booking-module-registry.php` - Central registry that discovers and loads modules dynamically
- **Module Base Classes**: Abstract classes in `includes/abstracts/` define interfaces for different module types
- **Conditional Loading**: Modules are loaded only if their directory exists, enabling feature toggling by file system presence

#### Directory Structure
```
includes/
├── abstracts/           # Abstract base classes and interfaces
├── core/               # Core functionality classes
│   ├── field-renderers/    # Form field rendering system
│   └── calendar-rules/     # Calendar validation rules
├── modules/            # Feature modules (conditionally loaded)
│   ├── booking/           # Core booking functionality
│   ├── payments/          # Payment processors (premium)
│   ├── notifications/     # Email system
│   ├── help/             # Help documentation
│   └── integrations/     # Third-party integrations
├── admin/              # Admin-specific classes
└── helpers/            # Utility functions
```

### Key Architectural Patterns

#### Module System
- **Dynamic Discovery**: Module registry scans filesystem for module files
- **Conditional Loading**: Premium modules only load with valid license
- **Singleton Pattern**: Module registry uses singleton for centralized management
- **Abstract Base Classes**: All modules extend `AIOHM_BOOKING_Module_Abstract`

#### Security Framework
- **Input Sanitization**: All user inputs sanitized via `AIOHM_BOOKING_Security_Helper`
- **Nonce Verification**: CSRF protection on all forms
- **Database Security**: Prepared statements for all queries
- **File Upload Security**: Type validation and secure handling

#### Settings Management
- **Centralized Configuration**: Settings stored in `aiohm_booking_settings` option
- **Module-Specific Settings**: Each module can have dedicated settings
- **Migration System**: Automatic settings migration between versions

### Database Schema
- **aiohm_booking_order**: Order/booking records
- **aiohm_booking_calendar_data**: Calendar availability data  
- **aiohm_booking_email_logs**: Email notification logs

### Integration Points

#### WordPress Integration
- **Custom Post Types**: Uses `aiohm_booking_event` post type
- **Shortcode System**: Multiple shortcodes for different booking modes
- **REST API**: Custom endpoints in `class-aiohm-booking-rest-api.php`
- **AJAX Handlers**: Admin and frontend AJAX in dedicated classes

#### Payment Integration
- **Stripe Module**: `includes/modules/payments/stripe/` (premium feature)
- **Payment Abstracts**: Base classes for implementing payment processors

#### Third-Party Integrations
- **EventOn**: Plugin integration for event importing
- **Facebook**: Event import capabilities

### Field Rendering System
Dynamic form field rendering using factory pattern:
- **Factory**: `AIOHM_Booking_Field_Renderer_Factory`
- **Renderers**: Specific field types in `includes/core/field-renderers/`
- **Interface**: `AIOHM_Booking_Field_Renderer_Interface`

### Asset Management
- **CSS/JS Loading**: `class-aiohm-booking-assets.php` handles conditional asset loading
- **Frontend Assets**: Located in `assets/` directory
- **Module Assets**: Each module can have its own assets folder

### Error Handling
- **Centralized Logging**: `class-aiohm-booking-error-handler.php`
- **Module Error Handling**: `class-aiohm-booking-module-error-handler.php`
- **Debug Mode**: Configurable via `AIOHM_BOOKING_DEBUG` constant

## Development Guidelines

### Module Development
- Extend `AIOHM_BOOKING_Module_Abstract` for new modules
- Implement `get_ui_definition()` static method for module registration
- Place module files in appropriate subdirectory under `includes/modules/`
- Use naming convention: `class-aiohm-booking-module-{name}.php`

### Security Considerations  
- Always sanitize input using `AIOHM_BOOKING_Security_Helper::sanitize_input()`
- Use prepared statements for database queries
- Implement nonce verification for forms
- Follow WordPress security best practices

### Database Operations
- Use WordPress database classes (`$wpdb`)
- Implement proper table creation in module `on_activation()` methods
- Cache frequently accessed data appropriately

### File Organization
- Keep module-specific functionality within module directories
- Use abstracts for shared functionality
- Place utilities in `includes/helpers/`
- Follow WordPress coding standards

### Premium Feature Implementation
- Check license status via Freemius: `aiohm_booking_fs()->can_use_premium_code__premium_only()`
- Mark premium modules with `is_premium` in UI definition
- Implement graceful degradation for free users

## Shortcode Reference

### Main Booking Forms
- `[aiohm_booking]` - Main booking form (auto-detects mode)
- `[aiohm_booking mode="accommodations"]` - Accommodation booking only
- `[aiohm_booking mode="tickets"]` - Event tickets only  
- `[aiohm_booking mode="both"]` - Combined booking
- `[aiohm_booking_checkout]` - Checkout page

### Common Parameters
- `mode`: "auto", "accommodations", "tickets", "both"
- `theme`: Styling theme
- `show_title`: Display form title
- `event_id`: Specific event ID
- `accommodation_id`: Specific accommodation ID

## Key Configuration

### Plugin Constants
- `AIOHM_BOOKING_VERSION`: Current plugin version
- `AIOHM_BOOKING_DIR`: Plugin directory path
- `AIOHM_BOOKING_URL`: Plugin URL
- `AIOHM_BOOKING_DEBUG`: Enable debug logging

### Important Settings
- Currency and formatting options
- Module enable/disable flags
- Deposit percentage configuration
- Email notification settings

### Environment Configuration
The plugin supports environment-based configuration for advanced setups, including Stripe keys, SMTP settings, and debug options.