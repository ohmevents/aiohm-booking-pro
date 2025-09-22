/**
 * AIOHM Booking - Preset Email Templates
 * This file contains the subject and content for the various email template presets.
 * Note: For brevity, only a few templates are included here. You can expand this object
 * with content for all 64 email types, using the direct-response copy from your project.
 */

const aiohmPresetTemplates = {
    'booking_confirmation_user': {
        'professional': {
            subject: 'Your booking #{booking_id} is confirmed, {first_name}',
            content: `Dear {first_name},\n\nYour reservation has been successfully confirmed and we're preparing everything for your arrival.\n\nThis isn't just another booking confirmation... This is your gateway to an exceptional experience that begins the moment you receive this email.\n\nYour Booking Details:\n• Booking Reference: #{booking_id}\n• Arrival: {check_in}\n• Departure: {check_out}\n• Accommodation: {rooms}\n• Total Investment: {total_amount}\n\nHere's what happens next:\nWithin the next 48 hours, you'll receive your comprehensive arrival guide containing exclusive access codes and local insider recommendations from our concierge team.\n\nWe're committed to ensuring your stay exceeds every expectation, {first_name}.\n\nProfessionally yours,\n{site_name} Guest Experience Team\n\nP.S. Keep an eye on your inbox... we're sending you something special that most guests never see. It's our way of saying thank you for choosing us over the competition.`
        },
        'friendly': {
            subject: '🎉 {first_name}, your getaway is confirmed!',
            content: `Hey there {first_name}! 👋\n\nWOW! We just got your booking and honestly... we're as excited as you probably are right now!\n\nYour reservation is 100% confirmed and we're already rolling out the red carpet (well, maybe not literally... but you get the idea 😄).\n\nLet's Talk Details: ✨\n• Your Booking: #{booking_id}\n• Check-in Day: {check_in}\n• Farewell Day: {check_out}\n• Your Home Away from Home: {rooms}\n• Investment in Memories: {total_amount}\n\nHere's the scoop on what happens next...\nWe're not just going to leave you hanging! In the next couple of days, expect a super detailed welcome package that includes easy check-in instructions and our favorite local spots that tourists never find.\n\nSeriously though, {first_name}... we can't wait to meet you!\n\nTalk soon! 💙\nThe {site_name} Family\n\nP.S. 🤫 We're cooking up something special for you... let's just say most of our guests tell us it's their favorite part of staying with us. Keep your eyes peeled!`
        },
        'luxury': {
            subject: 'Your distinguished reservation awaits, {first_name}',
            content: `Dear Esteemed Guest {first_name},\n\nIt is our distinct honor to confirm your reservation and welcome you to our exclusive circle of discerning travelers.\n\nYour booking represents more than accommodation... It signifies your membership into an elite experience curated for those who appreciate the extraordinary.\n\nYour Exclusive Arrangements:\n◆ Reservation Code: #{booking_id}\n◆ Arrival: {check_in}\n◆ Departure: {check_out}\n◆ Curated Accommodation: {rooms}\n◆ Total Investment: {total_amount}\n\nYour Concierge Experience Begins Now:\nWithin the next 24-48 hours, our dedicated concierge team will personally deliver personalized arrival protocols and curated local experiences unavailable to the general public.\n\nYour satisfaction is not merely our goal... it is our unwavering commitment.\n\nWith distinguished regards,\n{site_name} Concierge Services\n\nP.S. As a valued guest, you will receive our signature welcome amenity - an exclusive offering reserved solely for our most discerning clientele. This gesture reflects our appreciation for your distinguished choice.`
        },
        'minimalist': {
            subject: 'Confirmed: {booking_id}',
            content: `{first_name},\n\nYour booking is confirmed.\n\nDetails:\nID: #{booking_id}\nCheck-in: {check_in}\nCheck-out: {check_out}\nRoom: {rooms}\nTotal: {total_amount}\n\nNext steps:\n• Check-in instructions arriving within 48 hours\n• Questions? Reply to this email\n\nThank you,\n{site_name}\n\nP.S. Expecting arrival details shortly. Everything you need will be delivered on time.`
        }
    },
    'payment_reminder_1': {
        'professional': {
            subject: 'Payment Reminder: Complete Your Booking #{booking_id}',
            content: `Dear {first_name},\n\nYour upcoming stay is just around the corner, but we notice your final payment is still pending for booking #{booking_id}.\n\nDon't worry - this happens more often than you'd think, and we've made it super easy to resolve.\n\nOutstanding Balance Information:\n• Booking Reference: #{booking_id}\n• Check-in Date: {check_in}\n• Outstanding Amount: {total_amount}\n• Payment Deadline: 72 hours before arrival\n\nComplete your payment in under 2 minutes using our secure payment portal.\n\nProfessionally yours,\n{site_name} Guest Services`
        },
        'friendly': { subject: 'Hey {first_name}! Don\'t lose your perfect dates 😅', content: 'Friendly payment reminder content...' },
        'luxury': { subject: 'Distinguished guest: Premium reservation payment required', content: 'Luxury payment reminder content...' },
        'minimalist': { subject: 'Payment due: #{booking_id}', content: 'Minimalist payment reminder content...' }
    }
    // ... Add other template keys like 'booking_cancelled_user', 'check_in_instructions', etc.
};