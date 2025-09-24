# AIOHM Booking Pro - AI Agent Instructions

## Architecture Overview

**WordPress Plugin** for event booking and accommodation management with modular architecture. Core entry point: `aiohm-booking-pro.php` initializes Freemius licensing and boots the main `AIOHM_Booking` class.

### Module System (Critical Pattern)
- **Auto-discovery**: Registry scans `includes/modules/` and loads modules based on filesystem presence and Freemius licensing
- **Abstract Classes**: Extend `AIOHM_BOOKING_Module_Abstract`, `AIOHM_BOOKING_Settings_Module_Abstract`, etc.
- **Premium Gating**: Check `aiohm_booking_fs()->is_paying()` before accessing premium features
- **Categories**: `booking/` (core), `admin/`, `payments/` (premium), `ai/` (premium), `integrations/` (premium)

### Data Architecture (v2.0.3 Migration)
**CRITICAL**: Events migrated from serialized arrays to Custom Post Types. Always use cross-module access methods:

```php
// ✅ CORRECT: Cross-module event access
$events = AIOHM_BOOKING_Module_Tickets::get_events_data();

// ❌ AVOID: Direct array access (deprecated)
$events = get_option('aiohm_booking_tickets_settings', [])['events'];
```

**Storage Patterns**:
- Events: `aiohm_booking_event` CPT (since v2.0.3)
- Accommodations: `aiohm_accommodation` CPT (since v1.0)
- Orders: `wp_aiohm_booking_order` custom table
- Calendar: `wp_aiohm_booking_calendar_data` table

### Key Classes & Patterns

**Field Rendering**: Factory pattern with `AIOHM_Booking_Field_Renderer_Factory` and renderers for text, select, checkbox, etc.

**Security**: All input through `AIOHM_BOOKING_Validation`, WordPress nonces for CSRF, `$wpdb->prepare()` for queries.

**Assets**: Unified CSS in `assets/css/aiohm-booking-unified.css`, module-specific JS files conditionally loaded.

## Development Workflows

### Setup Commands
```bash
composer install  # PHP dependencies (Stripe SDK)
wp plugin activate aiohm-booking-pro  # Activate in WordPress
wp cache flush  # Clear caches after changes
```

### Module Development
1. Create directory in `includes/modules/{category}/`
2. Extend appropriate abstract class
3. Include `assets/` subdirectory if needed
4. Module auto-discovers on next page load

### Premium Feature Development
- Gate with `aiohm_booking_fs()->is_paying()`
- Mark premium directories with `@fs_premium_only` annotation
- Use conditional loading in registry

## Code Patterns & Conventions

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

### Template System
Templates in `templates/` with partials in `templates/partials/`:
- Main shortcodes: `aiohm-booking-shortcode.php`
- Booking flow: `aiohm-booking-checkout.php`, `aiohm-booking-success.php`
- Calendar: `aiohm-booking-calendar.php`

### Error Handling
- Global: `AIOHM_BOOKING_Error_Handler`
- Module-specific: `AIOHM_BOOKING_Module_Error_Handler`
- Validation errors via `AIOHM_BOOKING_Validation`

## Key Files & Directories

- `aiohm-booking-pro.php` - Main entry point, Freemius init
- `includes/core/` - Core classes (Settings, Assets, Security)
- `includes/abstracts/` - Base classes for modules
- `includes/helpers/` - Utility classes (Validation, Date handling)
- `templates/` - Frontend templates
- `assets/css/aiohm-booking-unified.css` - Main stylesheet
- `assets/js/` - Module-specific JavaScript files

## Demo Mode & Testing

Plugin supports WordPress Playground with demo mode:
- All Pro features unlocked
- Demo Stripe keys configured
- Sample data auto-populated
- Demo watermark on frontend

## Common Pitfalls

- **Never access event data directly** - use `AIOHM_BOOKING_Module_Tickets::get_events_data()`
- **Check licensing** before premium feature access
- **Use module registry pattern** for new features
- **Maintain backward compatibility** during data migrations
- **Validate all input** through `AIOHM_BOOKING_Validation`
- **Use WordPress query functions** or `$wpdb->prepare()` for database operations</content>
<parameter name="filePath">/Users/ohm/Sites/Local Sites/ohm/wp-content/plugins/aiohm-booking-pro/.github/copilot-instructions.md