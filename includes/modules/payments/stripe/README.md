# Stripe Payment Module

This folder contains all Stripe payment integration files for AIOHM Booking.

## Structure

```
stripe/
├── class-aiohm-booking-module-stripe.php  # Main Stripe module class
├── assets/                               # Stripe-specific assets
│   ├── css/                              # Stripe CSS files
│   │   └── aiohm-booking-stripe-frontend.css
│   ├── js/                               # Stripe JavaScript files
│   │   ├── aiohm-booking-stripe-frontend.js
│   │   └── stripe-settings.js
│   └── images/                           # Stripe images
│       └── aiohm-booking-stripe.png
└── README.md                             # This file
```

## Easy Removal

To completely remove Stripe functionality:

1. Delete this entire `stripe/` folder
2. The plugin will automatically detect the removal and hide Stripe options

## Easy Installation

To add/restore Stripe functionality:

1. Place the `stripe/` folder in `includes/modules/payments/`
2. The plugin will automatically discover and load the module

## Dependencies

- PHP 7.4+
- Stripe PHP SDK (included in vendor/)
- WordPress 6.2+