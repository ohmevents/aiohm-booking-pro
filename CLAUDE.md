# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Testing & Quality Assurance
```bash
# Install development dependencies
npm install

# Run CSS linting
npx stylelint "assets/css/**/*.css"

# Install PHP dependencies for premium features
composer install

# WordPress CLI commands for testing
wp plugin activate aiohm-booking-pro
wp cache flush
```

### No Build Process
This is a WordPress plugin that doesn't require a build step. CSS and JavaScript files are served directly from the `assets/` directory.

## Architecture Overview

This is a **WordPress Premium Plugin** for event booking and accommodation management built with a **modular architecture** and **Freemius licensing**.

### Core Entry Point
- `aiohm-booking-pro.php` - Main plugin file that initializes Freemius licensing and boots the `AIOHM_Booking` singleton class

### Module System (Critical Pattern)
The plugin uses **auto-discovery** for modules:
- **Registry Pattern**: `AIOHM_BOOKING_Module_Registry` scans `includes/modules/` and loads modules based on filesystem presence and licensing
- **Abstract Classes**: All modules extend `AIOHM_BOOKING_Module_Abstract`, settings modules extend `AIOHM_BOOKING_Settings_Module_Abstract`
- **Premium Gating**: Check `aiohm_booking_fs()->is_paying()` before accessing premium features
- **Categories**: 
  - `booking/` - Core functionality (free)
  - `admin/` - Admin interface modules (free)  
  - `payments/` - Payment processors (premium)
  - `ai/` - AI integrations (premium)
  - `integrations/` - Third-party integrations (premium)

### Data Architecture (v2.0.3+ Migration)
**CRITICAL**: Events migrated from serialized arrays to Custom Post Types. Always use cross-module access methods:

```php
// ✅ CORRECT: Cross-module event access
$events = AIOHM_BOOKING_Module_Tickets::get_events_data();

// ❌ AVOID: Direct array access (deprecated)
$events = get_option('aiohm_booking_tickets_settings', [])['events'];
```

**Storage Patterns**:
- **Events**: `aiohm_booking_event` Custom Post Type (since v2.0.3)
- **Accommodations**: `aiohm_accommodation` Custom Post Type  
- **Orders**: `wp_aiohm_booking_order` custom table
- **Calendar Data**: `wp_aiohm_booking_calendar_data` custom table
- **Settings**: WordPress options with centralized access via `AIOHM_BOOKING_Settings`

### Core Classes & Systems

**Field Rendering System**: Factory pattern with `AIOHM_Booking_Field_Renderer_Factory` creating renderers for text, select, checkbox, radio, color, etc.

**Security Layer**: All input validated through `AIOHM_BOOKING_Validation`, WordPress nonces for CSRF protection, `$wpdb->prepare()` for database queries.

**Asset Management**: Unified CSS in `assets/css/aiohm-booking-unified.css`, module-specific JS files conditionally loaded via `AIOHM_BOOKING_Assets`.

## Key Development Patterns

### Data Access
```php
// Accommodations (standard WordPress CPT)
$accommodations = get_posts(['post_type' => 'aiohm_accommodation', 'numberposts' => -1]);

// Settings (centralized access)
$settings = AIOHM_BOOKING_Settings::get_all();
$module_setting = $module_instance->get_setting('key');
```

### AJAX & REST API
- Register AJAX handlers via `AIOHM_BOOKING_Ajax_Registration_Manager`
- REST endpoints through `AIOHM_BOOKING_REST_API`
- Checkout flow uses `AIOHM_BOOKING_Checkout_Ajax`

### Module Development
1. Create directory in `includes/modules/{category}/`
2. Extend appropriate abstract class (`AIOHM_BOOKING_Module_Abstract` or `AIOHM_BOOKING_Settings_Module_Abstract`)
3. Include `assets/` subdirectory if module needs CSS/JS
4. Module auto-discovers on next page load

### Premium Feature Development
- Gate features with `aiohm_booking_fs()->is_paying()`
- Mark premium directories with `@fs_premium_only` annotation
- Use conditional loading in registry

## Directory Structure

```
includes/
├── core/                    # Core classes (Settings, Assets, Security, etc.)
├── abstracts/              # Base classes for modules and interfaces
├── helpers/                # Utility classes (Validation, Date handling)
├── admin/                  # Admin-specific classes
└── modules/               # Feature modules
    ├── booking/           # Core booking functionality (free)
    ├── admin/            # Admin interface modules (free)
    ├── payments/         # Payment processors (premium)
    ├── ai/              # AI integrations (premium)
    └── integrations/    # Third-party integrations (premium)

templates/                 # Frontend templates and partials
assets/                   # CSS, JavaScript, and image assets
```

## Important Notes

### Data Migration Awareness
The plugin underwent a major data migration in v2.0.3 from serialized arrays to Custom Post Types. Always use the official access methods rather than direct option access.

### Freemius Integration
- All premium features are gated through Freemius licensing checks
- Demo mode supported for WordPress Playground with all Pro features unlocked
- Module registry respects licensing tiers

### Security Practices
- All input validation through `AIOHM_BOOKING_Validation` class
- WordPress nonces for CSRF protection
- Prepared statements for database operations
- Security headers configured via `AIOHM_BOOKING_Security_Config`

### Common Pitfalls
- **Never access event data directly** - use `AIOHM_BOOKING_Module_Tickets::get_events_data()`
- **Check licensing** before accessing premium features  
- **Use module registry pattern** for new feature development
- **Maintain backward compatibility** during data migrations
- **Validate all input** through `AIOHM_BOOKING_Validation`