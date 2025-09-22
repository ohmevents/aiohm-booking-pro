# Troubleshooting Guide

Common issues and solutions for AIOHM Booking Pro users.

## 🚨 Quick Diagnosis

### Is the Plugin Activated?
1. Go to **Plugins** in your WordPress admin
2. Look for "AIOHM Booking Pro"
3. Status should show "Active" in green

### Are Shortcodes Working?
Test basic functionality:
1. Create a new page/post
2. Add shortcode: `[aiohm_booking]`
3. Preview the page
4. Should display a booking form

## 🔧 Common Issues & Solutions

### "Booking Form Not Showing"

**Symptoms:**
- Page shows shortcode text instead of form
- Blank space where form should be
- JavaScript errors in browser console

**Solutions:**
1. **Check Shortcode Syntax**
   - Ensure correct format: `[aiohm_booking]`
   - No extra spaces or characters
   - Proper quotation marks

2. **Plugin Conflicts**
   - Temporarily deactivate other plugins
   - Test booking form
   - Reactivate plugins one by one

3. **Theme Issues**
   - Switch to default WordPress theme (Twenty Twenty-One)
   - Test booking form
   - Contact theme developer if issue persists

4. **JavaScript Errors**
   - Open browser developer tools (F12)
   - Check Console tab for red error messages
   - Note error details for support

### "Payment Processing Failed"

**Symptoms:**
- Stripe/PayPal payments not going through
- "Payment declined" messages
- Cards being declined

**Solutions:**
1. **API Keys Configuration**
   - Go to **AIOHM Booking > Settings > Payments**
   - Verify Stripe Publishable/Secret keys
   - Test keys in Stripe dashboard

2. **SSL Certificate**
   - Payments require HTTPS
   - Check site has valid SSL certificate
   - Contact hosting provider if needed

3. **Stripe Webhooks**
   - Ensure webhook URL is configured in Stripe
   - URL format: `https://yoursite.com/wp-admin/admin-ajax.php?action=aiohm_booking_stripe_webhook`
   - Test webhook in Stripe dashboard

4. **Card Testing**
   - Use Stripe test cards: `4242 4242 4242 4242`
   - Any future expiry date
   - Any CVC code

### "Emails Not Sending"

**Symptoms:**
- No booking confirmation emails
- Emails going to spam
- SMTP connection errors

**Solutions:**
1. **WordPress Email Settings**
   - Go to **AIOHM Booking > Settings > Notifications**
   - Verify "From" email address
   - Test email sending

2. **SMTP Configuration**
   - Use SMTP plugin (WP Mail SMTP, Easy WP SMTP)
   - Configure with your email provider settings
   - Test SMTP connection

3. **Spam Filters**
   - Check recipient spam/junk folders
   - Add booking emails to safe senders
   - Use recognizable sender name

4. **Email Templates**
   - Verify email content is not triggering spam filters
   - Avoid excessive caps, special characters
   - Include unsubscribe links if required

### "Calendar Not Loading"

**Symptoms:**
- Calendar shows blank or loading forever
- Date picker not working
- Availability not displaying

**Solutions:**
1. **Browser Cache**
   - Hard refresh page (Ctrl+F5 or Cmd+Shift+R)
   - Clear browser cache completely
   - Try different browser

2. **JavaScript Conflicts**
   - Deactivate plugins one by one
   - Check for jQuery conflicts
   - Test with default theme

3. **Plugin Updates**
   - Ensure AIOHM Booking Pro is updated
   - Check WordPress is updated
   - Verify theme compatibility

4. **Date Format Issues**
   - Check WordPress date settings
   - Verify timezone configuration
   - Test with different date formats

### "Bookings Not Saving"

**Symptoms:**
- Form submits but no booking record
- Orders not appearing in admin
- Database errors

**Solutions:**
1. **Database Permissions**
   - Check WordPress database user permissions
   - Verify tables were created during activation
   - Run database repair if needed

2. **Form Validation**
   - Check required fields are filled
   - Verify date formats
   - Test with minimal form data

3. **Server Resources**
   - Check PHP memory limit (128MB minimum)
   - Verify execution time limits
   - Monitor server error logs

4. **Plugin Conflicts**
   - Deactivate security plugins temporarily
   - Test booking creation
   - Check for AJAX conflicts

## 🔍 Advanced Troubleshooting

### Debug Mode Activation

Enable detailed logging for support:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('AIOHM_BOOKING_DEBUG', true);
```

Access debug logs at: `wp-content/debug.log`

### System Information

Gather information for support:

1. **WordPress Version:** Dashboard > Updates
2. **PHP Version:** Tools > Site Health > Info
3. **Plugin Versions:** Plugins page
4. **Theme:** Appearance > Themes
5. **Server Type:** Hosting provider dashboard

### Common Error Messages

**"Nonce verification failed"**
- Form session expired, refresh page
- Browser cookies blocked
- Security plugin interference

**"Maximum execution time exceeded"**
- Increase PHP max_execution_time
- Contact hosting provider
- Optimize server resources

**"Allowed memory size exhausted"**
- Increase PHP memory_limit
- Deactivate unnecessary plugins
- Use lighter theme

## 🛠️ Manual Fixes

### Clear Plugin Cache
```php
// Add to functions.php temporarily
add_action('init', function() {
    if (isset($_GET['clear_aiohm_cache'])) {
        // Clear transients and caches
        delete_transient('aiohm_booking_calendar_data');
        wp_cache_flush();
        echo 'Cache cleared';
        exit;
    }
});
```
Visit: `yoursite.com?clear_aiohm_cache=1`

### Reset Plugin Settings
```php
// Run in WP Admin > Tools > WP Console
delete_option('aiohm_booking_settings');
delete_option('aiohm_booking_version');
// Plugin will recreate defaults on next load
```

### Reinstall Database Tables
1. Deactivate plugin
2. Delete plugin (keeps settings)
3. Reinstall and activate
4. Tables will be recreated

## 📞 Getting Help

### Self-Service Resources
- 📖 **Documentation**: [docs.ohm.events](https://docs.ohm.events)
- 🎥 **Video Tutorials**: Step-by-step guides
- 💬 **Community Forum**: User discussions
- 🔍 **Knowledge Base**: Search common issues

### Premium Support
For Pro users with active licenses:
- 🎧 **Priority Support**: Direct developer assistance
- 📧 **Email Support**: 24-48 hour response time
- 📱 **Live Chat**: Real-time help during business hours
- 📞 **Phone Support**: Complex issue resolution

### Before Contacting Support

**Prepare Information:**
- WordPress version and PHP version
- Plugin version and license status
- Detailed description of the issue
- Steps to reproduce the problem
- Browser console errors (if applicable)
- Server error logs (if accessible)

**Test Environment:**
- Create staging site if possible
- Test with default theme
- Deactivate other plugins temporarily
- Use test data, not live bookings

## 🚀 Preventive Maintenance

### Regular Tasks
- **Weekly:** Check for plugin updates
- **Monthly:** Review booking data and reports
- **Quarterly:** Test payment processing
- **Annually:** Audit user permissions and security

### Performance Optimization
- Use caching plugin (WP Rocket, W3 Total Cache)
- Optimize images and media files
- Monitor database size and cleanup
- Regular security scans

### Backup Strategy
- Daily automated backups
- Test backup restoration quarterly
- Store backups offsite
- Document restoration procedures

---

**Still having issues?** Our support team is here to help. Include as much detail as possible when contacting us.