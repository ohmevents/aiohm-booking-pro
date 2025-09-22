# AIOHM Booking - Notifications Module

This is a complete, self-contained notifications module for the AIOHM Booking plugin.

## Module Structure

```
notifications/
├── README.md                                           # This documentation file
├── class-aiohm-booking-module-notifications.php      # Main module class
├── assets/
│   └── js/
│       ├── aiohm-booking-notifications-admin.js      # Admin interface JavaScript
│       └── aiohm-booking-notifications-template.js   # Template-specific JavaScript
└── templates/
    └── aiohm-booking-notifications.php                # Admin page template
```

## Features

- **SMTP Configuration**: Professional email sending with custom SMTP settings
- **Email Templates**: Customizable templates for all booking notifications
- **Email Logging**: Track sent emails and troubleshoot delivery issues
- **Multi-language Support**: Templates support translation and localization
- **Admin Interface**: Complete admin page for managing all notification settings

## Module Integration

This module follows the AIOHM Booking module architecture:

- Extends `AIOHM_BOOKING_Settings_Module_Abstract`
- Uses automatic discovery via the module registry
- Self-contained with all assets in module directory
- Follows WordPress coding standards

## Files Moved from Core

This module was reorganized from the core plugin structure:
- Main class moved from `includes/modules/booking/` 
- Template moved from `templates/`
- JavaScript assets moved from `assets/js/`

All file paths have been updated to use relative paths within the module directory.