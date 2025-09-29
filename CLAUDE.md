# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AIOHM Booking Pro is a WordPress plugin for event booking and accommodation management. It follows a modular architecture with separate modules for different functionality areas. The plugin supports both free and premium features through Freemius licensing.

## Commands

This WordPress plugin doesn't use traditional build tools like npm, gulp, or webpack. No specific build, lint, or test commands are configured. Development involves direct PHP file editing and testing within the WordPress environment.

## Architecture

### Core Structure
- **Main Plugin File**: `aiohm-booking-pro.php` - Plugin initialization, Freemius integration, and WordPress hooks
- **Core Classes**: Located in `includes/core/` - Central functionality like module registry, settings, admin, assets
- **Modules**: Located in `includes/modules/` - Self-contained feature modules following a standardized interface
- **Abstracts**: Located in `includes/abstracts/` - Base classes and interfaces that modules extend
- **Templates**: Located in `templates/` - PHP template files for frontend output
- **Assets**: Located in `assets/` - CSS, JavaScript, and image files

### Module System
The plugin uses a dynamic module loading system centered around `AIOHM_BOOKING_Module_Registry`:

- **Module Discovery**: Automatically discovers modules by scanning `includes/modules/` directory
- **Module Loading**: Loads modules that extend `AIOHM_BOOKING_Module_Abstract`
- **Conditional Loading**: Modules can be disabled by removing their directories (premium modules require license)
- **Module Categories**: Organized into booking, payments, ai, integrations, notifications, help

### Key Components

#### Module Abstract Base Class (`abstract-aiohm-booking-module.php`)
All modules must extend this class and implement:
- `get_ui_definition()` - Module metadata and configuration
- `init_hooks()` - WordPress hooks initialization
- `get_settings_fields()` - Admin settings configuration
- `get_default_settings()` - Default setting values

#### Module Registry (`class-aiohm-booking-module-registry.php`)
Central system that:
- Discovers available modules in the filesystem
- Handles module loading and initialization
- Manages module dependencies and premium checks
- Provides module access methods for other components

#### Settings System
- **Global Settings**: Stored in `aiohm_booking_settings` option
- **Module Settings**: Stored in `aiohm_booking_{module_id}_settings` options
- **Field Rendering**: Uses factory pattern for consistent form field rendering
- **Sanitization**: Centralized through `AIOHM_Booking_Module_Settings_Manager`

#### Premium Features
- **Freemius Integration**: Premium licensing and feature gating
- **Premium Modules**: Located in subdirectories with `__premium_only` suffix
- **Feature Checks**: Uses `aiohm_booking_fs()->can_use_premium_code__premium_only()`
- **Demo Mode**: Special handling for WordPress Playground demonstrations

### File Naming Conventions
- **Classes**: `class-{feature-name}.php` (e.g., `class-aiohm-booking-module-stripe.php`)
- **Abstracts**: `abstract-{feature-name}.php` or `interface-{feature-name}.php`
- **Templates**: `{feature-name}.php` (e.g., `aiohm-booking-calendar.php`)
- **Assets**: `aiohm-booking-{feature-name}.{ext}` (e.g., `aiohm-booking-admin.css`)

### WordPress Integration
- **Custom Post Types**: Uses `aiohm_booking_event` for events
- **Database Tables**: Custom tables for orders, calendar data, email logs
- **REST API**: Custom endpoints in `AIOHM_BOOKING_REST_API`
- **AJAX**: Handled through `AIOHM_BOOKING_Admin_Ajax` and module-specific handlers
- **Shortcodes**: `[aiohm_booking]`, `[aiohm_booking_checkout]`, `[aiohm_booking_accommodations]`

### Security Features
- **Security Config**: `AIOHM_BOOKING_Security_Config` for headers and validation
- **Input Sanitization**: Centralized through settings manager and field renderers
- **Nonce Protection**: Used for AJAX and form submissions
- **Capability Checks**: WordPress capability system for admin access

### Error Handling
- **Module Error Handler**: `AIOHM_BOOKING_Module_Error_Handler` for module-specific errors
- **Global Error Handler**: `AIOHM_BOOKING_Error_Handler` for system-wide error management
- **Validation**: Built into settings system with field-level validation

## Development Guidelines

### Adding New Modules
1. Create module directory in appropriate category (`booking/`, `payments/`, `ai/`, etc.)
2. Implement module class extending `AIOHM_BOOKING_Module_Abstract`
3. Define `get_ui_definition()` with module metadata
4. Implement required abstract methods
5. Add module-specific assets if needed
6. Module will be auto-discovered and loaded by the registry

### Settings Implementation
- Use the field renderer factory for consistent UI
- Implement proper sanitization callbacks
- Follow the established field types: text, textarea, checkbox, select, number, email, url, color, custom
- Provide meaningful descriptions and default values

### Asset Management
- CSS files are consolidated into unified files (`aiohm-booking-admin.css`, `aiohm-booking-unified.css`)
- JavaScript files are modular and loaded per-module as needed
- Use `aiohm_booking_asset_url()` helper for consistent asset URLs
- Assets are enqueued through the module system's `enqueue_admin_assets()` and `frontend_enqueue_assets()` methods

### Database Operations
- Use WordPress standards for database operations
- Custom tables are created during plugin activation
- Cache frequently accessed data using WordPress transients
- Module registry caches discovered modules for 12 hours

### Internationalization
- Text domain: `aiohm-booking-pro`
- Translation files in `languages/` directory
- Support for custom language settings per installation
- Uses WordPress automatic translation loading since 4.6+