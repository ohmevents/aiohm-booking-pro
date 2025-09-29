# AIOHM Booking Pro - AI Developer Instructions

## Project Architecture

AIOHM Booking Pro is a modular WordPress plugin for event booking and accommodation management with Freemius licensing integration. The plugin follows a dynamic module loading system with conditional premium features.

### Core Module System

**Module Registry** (`includes/core/class-aiohm-booking-module-registry.php`):
- Automatically discovers modules by scanning `includes/modules/` with 12-hour caching
- Loads modules extending `AIOHM_BOOKING_Module_Abstract` 
- Supports conditional loading (premium modules require license)
- Uses singleton pattern with `AIOHM_BOOKING_Module_Registry::instance()`

**Module Structure**:
```php
// All modules must extend abstract and implement these methods:
abstract public static function get_ui_definition(); // Module metadata
abstract protected function init_hooks();           // WordPress hooks
abstract public function get_settings_fields();    // Admin configuration
abstract protected function get_default_settings(); // Default values
```

**Premium Gating**:
- Premium modules in `/payments/`, `/ai/`, `/integrations/` directories
- Feature checks: `aiohm_booking_fs()->can_use_premium_code__premium_only()`
- Files with `__premium_only` suffix are automatically premium-gated
- Demo mode available for WordPress Playground testing

### Key Workflows

**Adding New Modules**:
1. Create in appropriate category: `includes/modules/{category}/class-aiohm-booking-module-{name}.php`
2. Module auto-discovered by registry (no manual registration needed)
3. Extend `AIOHM_BOOKING_Module_Abstract` or `AIOHM_BOOKING_Settings_Module_Abstract`
4. Implement required abstract methods, especially `get_ui_definition()`

**Database Operations**:
- Custom tables: `wp_aiohm_booking_order`, `wp_aiohm_booking_calendar_data`, `wp_aiohm_booking_email_logs`
- Created on activation via `create_database_tables()` in main plugin file
- Orders cached with `wp_cache_*` functions (5-minute expiry)
- Use prepared statements with `$wpdb->prepare()` for all queries

### WordPress Integration Patterns

**Shortcodes**: `[aiohm_booking]`, `[aiohm_booking_checkout]`, `[aiohm_booking_accommodations]`, `[aiohm_booking_events]`
- Unified template system via `templates/aiohm-booking-sandwich-template.php`
- Event data from `aiohm_booking_event` custom post type (migrated from arrays in v2.0.3)

**REST API**: Custom endpoints in `AIOHM_BOOKING_REST_API` class
- `/wp-json/aiohm-booking/v1/` namespace
- Handles orders, events, accommodations, calendar data
- Webhook endpoints for Stripe/PayPal payment processing

**Asset Management**:
- Consolidated CSS: `aiohm-booking-admin.css`, `aiohm-booking-unified.css`
- Modular JS per feature: `aiohm-booking-{feature-name}.js`
- Use `aiohm_booking_asset_url()` helper for consistent URLs
- Enqueued via module's `enqueue_admin_assets()` and `frontend_enqueue_assets()` methods

### Settings Architecture

**Storage Pattern**:
- Global: `aiohm_booking_settings` option
- Per-module: `aiohm_booking_{module_id}_settings` options
- Field rendering via factory pattern for consistent UI
- Sanitization through `AIOHM_Booking_Module_Settings_Manager`

### Development Environment

**No Build Tools**: Direct PHP development - no npm/webpack/gulp configured
**Testing**: WordPress environment testing only
**File Naming**: 
- Classes: `class-{feature-name}.php`
- Templates: `{feature-name}.php`  
- Assets: `aiohm-booking-{feature-name}.{ext}`

### Critical Integration Points

**Freemius Integration** (`aiohm-booking-pro.php`):
- SDK initialization with auto-skip opt-in to prevent blocking modals
- License status affects module loading via `is_module_directory_available()`
- Pricing page integration with custom assets

**Module Dependencies**:
- Abstract classes loaded first in `load_modules()`
- Premium check prevents loading paid modules for free users
- Module registry clears cache on license changes

When working with this codebase, remember the module system is the primary extension point - most features should be implemented as new modules rather than modifying core files.