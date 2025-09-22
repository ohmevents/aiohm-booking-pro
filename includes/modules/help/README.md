# Help Module

The Help Module provides documentation, system information, and support resources for the AIOHM Booking plugin.

## Structure

This module is completely independent and contains all its required files:

```
includes/modules/help/
├── class-aiohm-booking-module-help.php  # Main module class
├── assets/
│   ├── css/
│   │   └── aiohm-booking-help.css       # Help-specific styles
│   └── js/
│       └── aiohm-booking-help-admin.js  # Help page JavaScript
├── templates/
│   └── aiohm-booking-help.php           # Help page template
└── README.md                            # This file
```

## Features

- System information display
- Debug information collection and download
- Support request forms
- Feature request submission
- Documentation links
- Troubleshooting guides

## Independence

This module is completely self-contained:
- ✅ Contains its own CSS and JavaScript files
- ✅ Uses relative paths for all assets
- ✅ Self-handles asset enqueueing
- ✅ Contains its own template files
- ✅ No external dependencies beyond WordPress core and plugin base

## Asset Loading

The module automatically loads its assets when the help page is accessed:
- CSS: `assets/css/aiohm-booking-help.css`
- JavaScript: `assets/js/aiohm-booking-help-admin.js`
- Template: `templates/aiohm-booking-help.php`

## Development

To modify the help module:
1. Edit the main class in `class-aiohm-booking-module-help.php`
2. Update styles in `assets/css/aiohm-booking-help.css`
3. Modify functionality in `assets/js/aiohm-booking-help-admin.js`
4. Update the template in `templates/aiohm-booking-help.php`

All changes are contained within this module directory.