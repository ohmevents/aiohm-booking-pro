# AIOHM Booking Pro - Localization Guide

## Available Translations

### Completed Languages
- **Romanian (ro_RO)** - Complete translation with 200+ strings
- **English US (en_US)** - Reference/fallback file

### Translation Files Structure

```
languages/
├── aiohm-booking-pro.pot           # Template file for translators
├── aiohm-booking-pro-ro_RO.po      # Romanian translation source
├── aiohm-booking-pro-ro_RO.mo      # Romanian compiled translation
├── aiohm-booking-pro-en_US.po      # English US source
├── aiohm-booking-pro-en_US.mo      # English US compiled
└── README-LOCALIZATION.md          # This documentation
```

## How to Add a New Language

### Step 1: Create Translation Files
1. Copy the template: `cp aiohm-booking-pro.pot aiohm-booking-pro-[LOCALE].po`
2. Replace `[LOCALE]` with your language code (e.g., `fr_FR`, `de_DE`, `es_ES`)

### Step 2: Translate Strings
Edit the `.po` file and translate each `msgstr ""` entry:

```po
# Example
msgid "Accommodation"
msgstr "Votre_traduction_ici"
```

### Step 3: Update File Header
Update the header information in your `.po` file:

```po
"Language: [LOCALE]\n"
"Last-Translator: Your Name <your.email@example.com>\n"
"Language-Team: Language Name <language@example.com>\n"
"PO-Revision-Date: 2024-12-15T12:00:00+00:00\n"
```

### Step 4: Compile to Binary
Generate the `.mo` file:
```bash
msgfmt -o aiohm-booking-pro-[LOCALE].mo aiohm-booking-pro-[LOCALE].po
```

## Translation Coverage

### Core Features Covered
- ✅ Accommodation management interface
- ✅ Settings and configuration
- ✅ Error messages and validation
- ✅ User interface elements
- ✅ Form fields and labels
- ✅ Success/failure notifications
- ✅ Statistics and dashboard
- ✅ Accommodation types (Room, Villa, etc.)

### Translation Categories

#### 1. Accommodation Management (45 strings)
- Accommodation types and labels
- Management interface
- WordPress admin integration

#### 2. Settings & Configuration (38 strings)
- Booking settings
- Price configuration
- Feature toggles

#### 3. Error Messages & Security (25 strings)
- Validation errors
- Security notifications
- Permission messages

#### 4. Form Interface (42 strings)
- Form fields and labels
- Help text and descriptions
- Action buttons

#### 5. Statistics & Dashboard (20 strings)
- Statistics labels
- Status indicators
- Summary information

#### 6. General Interface (30+ strings)
- Common buttons and actions
- Navigation elements
- Generic messages

## Adding Missing Strings

If you find untranslated strings:

### 1. Update Source Code
Ensure all user-facing strings use translation functions:
```php
__( 'Your string', 'aiohm-booking-pro' )
esc_html__( 'Your string', 'aiohm-booking-pro' )
```

### 2. Regenerate Template
Update the `.pot` file with new strings:
```bash
wp i18n make-pot . languages/aiohm-booking-pro.pot --domain=aiohm-booking-pro
```

### 3. Update Translations
Add new strings to existing `.po` files and recompile.

## WordPress Integration

### Text Domain
The plugin uses the text domain: `aiohm-booking-pro`

### Loading Translations
Translations are automatically loaded by WordPress when:
1. The plugin is active
2. WordPress language is set to a supported locale
3. Corresponding `.mo` file exists in `/languages/` directory

### Priority Order
WordPress loads translations in this order:
1. `/wp-content/languages/plugins/aiohm-booking-pro-[LOCALE].mo`
2. `/wp-content/plugins/aiohm-booking-pro/languages/aiohm-booking-pro-[LOCALE].mo`

## Testing Translations

### Change WordPress Language
1. Go to Settings → General
2. Change "Site Language" to your target language
3. Save changes

### Verify Translations
1. Navigate to AIOHM Booking Pro pages
2. Check that strings appear in the selected language
3. Test forms and error messages

## Contribution Guidelines

### For Translators
1. Follow WordPress translation standards
2. Keep translations concise and user-friendly
3. Maintain consistency with WordPress core translations
4. Test translations in context

### For Developers
1. Always use translation functions for user-facing text
2. Provide translator comments for complex strings:
   ```php
   /* translators: %s: accommodation type name */
   sprintf( __( 'Add New %s', 'aiohm-booking-pro' ), $type )
   ```
3. Avoid concatenating translatable strings
4. Use plural forms when needed:
   ```php
   sprintf(
       _n( '%d accommodation', '%d accommodations', $count, 'aiohm-booking-pro' ),
       $count
   )
   ```

## Language Support Status

| Language | Code | Status | Completion | Contributor |
|----------|------|--------|------------|-------------|
| Romanian | ro_RO | ✅ Complete | 100% | AIOHM Team |
| English US | en_US | ✅ Reference | 100% | AIOHM Team |
| French | fr_FR | 🔄 Needed | 0% | - |
| German | de_DE | 🔄 Needed | 0% | - |
| Spanish | es_ES | 🔄 Needed | 0% | - |
| Italian | it_IT | 🔄 Needed | 0% | - |
| Dutch | nl_NL | 🔄 Needed | 0% | - |

## Need Help?

### Resources
- [WordPress i18n Handbook](https://developer.wordpress.org/plugins/internationalization/)
- [Poedit Translation Editor](https://poedit.net/)
- [GNU gettext Documentation](https://www.gnu.org/software/gettext/manual/)

### Support
For translation support or to contribute translations:
- Create an issue on the plugin repository
- Contact the AIOHM team
- Join the WordPress translation community

---

**Last Updated:** December 15, 2024  
**Plugin Version:** 2.0.4  
**Translation Template Version:** 1.0.0