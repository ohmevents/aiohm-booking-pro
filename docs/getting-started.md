# Getting Started with AIOHM Booking Pro

Welcome to AIOHM Booking Pro! This guide will help you get up and running with your new booking system in just a few minutes.

## 🎯 Quick Start (5 Minutes)

### Step 1: Install and Activate
1. Download the plugin from WordPress.org or your purchase confirmation
2. Go to **Plugins > Add New > Upload Plugin**
3. Select the zip file and click **Install Now**
4. Click **Activate Plugin**

### Step 2: Basic Setup
1. Navigate to **AIOHM Booking > Settings**
2. Set your **Currency** (USD, EUR, GBP, etc.)
3. Set **Deposit Percentage** (recommended: 30%)
4. Configure your **Business Details** (name, email, phone)

### Step 3: Create Your First Booking Item
Choose what you want to book:

#### For Event Tickets:
1. Go to **AIOHM Booking > Events**
2. Click **Add New Event**
3. Fill in event details (name, date, description, price)
4. Set ticket availability and pricing

#### For Accommodation:
1. Go to **AIOHM Booking > Accommodations**
2. Click **Add New Accommodation**
3. Add property details (name, description, pricing)
4. Set availability calendar

### Step 4: Add Booking Forms to Your Site
Use shortcodes to display booking forms:

```
[aiohm_booking] - Auto-detects based on your setup
[aiohm_booking mode="tickets"] - Event tickets only
[aiohm_booking mode="accommodations"] - Accommodation only
[aiohm_booking_checkout] - Checkout page
```

### Step 5: Test Your Setup
1. Visit a page with your booking shortcode
2. Fill out the booking form
3. Complete a test booking
4. Check your email for confirmations

## 📋 What You Can Book

### 🎟️ Events & Tickets
Perfect for:
- Workshops and seminars
- Concerts and performances
- Classes and courses
- Retreats and gatherings

**Features:**
- Multiple ticket types (Early Bird, Regular, VIP)
- Date and time selection
- Capacity limits
- Automatic availability management

### 🏠 Accommodation
Perfect for:
- Vacation rentals
- Retreat centers
- Bed & breakfasts
- Private event venues

**Features:**
- Room/accommodation management
- Deposit payments
- Availability calendars
- Multi-room bookings

### 🤝 Combined Bookings
Offer packages that include both events and accommodation:
- Retreat packages (workshop + lodging)
- Conference packages (tickets + hotel)
- Event + accommodation bundles

## 💰 Payment Options

### Free Version
- **Manual Payment**: Customers receive payment instructions via email
- Perfect for small operations or cash-only businesses

### Pro Version
- **Stripe Integration**: Secure credit card processing
- **PayPal**: PayPal account and card payments
- **Automatic Processing**: Instant payment confirmation
- **Recurring Payments**: For subscription-based bookings

## 📧 Email Notifications

The plugin automatically sends professional emails for:
- **Booking Confirmations**: Sent immediately after booking
- **Payment Reminders**: For pending manual payments
- **Cancellation Notices**: When bookings are cancelled
- **Admin Notifications**: New booking alerts

Customize email templates in **AIOHM Booking > Settings > Notifications**.

## 🎨 Customization

### Themes
Choose from built-in themes:
- **Default**: Clean, professional design
- **Minimal**: Simple, distraction-free
- **Modern**: Contemporary styling
- **Classic**: Traditional booking form

### Shortcode Options
```
[aiohm_booking
  theme="modern"
  show_title="true"
  event_id="123"
  button_text="Book Now"
]
```

### Custom Styling
Add custom CSS in **Appearance > Customize > Additional CSS**:

```css
/* Custom booking form styling */
.aiohm-booking-form {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
```

## 📊 Managing Bookings

### Admin Dashboard
Access all bookings at **AIOHM Booking > Orders**:
- View all bookings in one place
- Filter by date, status, or type
- Export booking data
- Process refunds
- Send customer communications

### Calendar Management
Visual calendar at **AIOHM Booking > Calendar**:
- Block dates for maintenance
- Set availability rules
- View booking conflicts
- Bulk availability updates

## 🔧 Advanced Features

### Deposit System
- Set percentage-based deposits (default 30%)
- Customers pay deposit upfront
- Balance due upon arrival
- Automatic calculations

### Private Bookings
- Allow customers to book entire properties privately
- Set minimum stay requirements
- Custom pricing for private bookings

### Calendar Rules
- Set blackout dates
- Minimum/maximum stay rules
- Seasonal pricing
- Availability patterns

## 🆘 Need Help?

### Quick Troubleshooting
- **Forms not showing?** Check shortcode syntax
- **Payments not processing?** Verify API keys in settings
- **Emails not sending?** Check SMTP configuration
- **Calendar not loading?** Clear browser cache

### Support Resources
- 📖 **Documentation**: Full guides and tutorials
- 🎥 **Video Tutorials**: Step-by-step walkthroughs
- 💬 **Community Forum**: Ask questions and share tips
- 🎧 **Premium Support**: Direct assistance for Pro users

## 🚀 Next Steps

Once you have basic bookings working:

1. **Set up payment processing** (Stripe/PayPal for Pro users)
2. **Customize email templates** to match your brand
3. **Configure advanced calendar rules**
4. **Add booking widgets** to sidebars
5. **Set up automated reminders**
6. **Integrate with your CRM** or accounting software

## 💡 Pro Tips

- **Start Simple**: Begin with one event or accommodation
- **Test Everything**: Always test bookings end-to-end
- **Backup Regularly**: Export booking data weekly
- **Monitor Availability**: Keep calendars up-to-date
- **Collect Feedback**: Ask customers for improvement suggestions

---

**Happy booking! 🎉**

*Built for conscious businesses by [OHM Events Agency](https://www.ohm.events)*