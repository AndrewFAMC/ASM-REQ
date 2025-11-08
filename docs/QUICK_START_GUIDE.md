# Quick Start Guide - New Features
**HCC Asset Management System v2.0**

## 🚀 Quick Overview

This guide will help you quickly test all the new features added to the system.

---

## ✨ New Features at a Glance

### 1. **Real-Time Notifications**
- 🔔 Sound alerts when new notifications arrive
- 💻 Desktop browser notifications
- ⚡ Faster updates (every 15 seconds)
- 🎨 Smooth animations

### 2. **Notification Preferences**
- ⚙️ Customize notification channels (email, browser, sound)
- 🎯 Choose which types of notifications you want
- 💾 Settings saved to your profile

### 3. **Enhanced Dashboards**
- 📊 New charts and metrics
- 📈 Request activity trends
- ⚠️ Low stock alerts
- 📋 Recent activity feed
- 📌 Quick stats cards

---

## 🎯 Quick Test (5 Minutes)

### Step 1: Test Notifications (2 min)

1. **Log in** to any dashboard
2. **Look for** the bell icon (🔔) in the header
3. **Click the bell** to see your notifications
4. **Click gear icon (⚙)** next to the bell
5. **Adjust preferences** and save

**Test a notification:**
```sql
-- Run in MySQL to create a test notification
INSERT INTO notifications (user_id, type, title, message, priority, created_at)
VALUES (YOUR_USER_ID, 'system_alert', 'Welcome to v2.0!', 'Check out the new features!', 'medium', NOW());
```
Replace `YOUR_USER_ID` with your actual user ID.

**Expected:** Bell shakes, sound plays (if enabled), notification appears!

---

### Step 2: Test Admin Dashboard (2 min)

1. **Log in as admin**
2. **Scroll down** to see new features:
   - **Request Trend Chart**: 7-day activity line chart
   - **Low Stock Alerts**: Yellow highlighted items
   - **Recent Activity**: Live feed of actions

3. **Hover over** the trend chart to see details
4. **Check if** low stock items are showing

---

### Step 3: Test Employee Dashboard (1 min)

1. **Log in as employee**
2. **Check the top** - 4 new stat cards:
   - 📦 My Assets
   - ⏳ Pending
   - ✅ Approved
   - 🔓 Available

3. **Switch tabs** and watch stats update automatically!

---

## 📋 Feature Checklist

Use this checklist to verify everything works:

### Notification System
- [ ] Bell icon appears in header
- [ ] Clicking bell shows dropdown
- [ ] Notification count badge displays correctly
- [ ] "Mark all read" button works
- [ ] Clicking a notification marks it as read
- [ ] Settings icon (gear) opens preferences page

### Notification Preferences
- [ ] Can access preferences page
- [ ] Toggle switches work
- [ ] Can enable/disable email notifications
- [ ] Can enable/disable browser notifications
- [ ] Can enable/disable sound notifications
- [ ] Can enable/disable specific notification types
- [ ] "Save Preferences" shows success message
- [ ] Settings persist after page reload

### Admin Dashboard
- [ ] All stat cards show correct numbers
- [ ] Request trend chart displays
- [ ] Trend chart is interactive (hover tooltips)
- [ ] Low stock alerts widget shows items ≤5 quantity
- [ ] Recent activity feed displays
- [ ] Activity feed shows user names and actions
- [ ] All existing charts still work
- [ ] Settings icon appears in header

### Employee Dashboard
- [ ] 4 quick stat cards display at top
- [ ] "My Assets" count is correct
- [ ] "Pending" count matches pending requests
- [ ] "Approved" count matches approved requests
- [ ] "Available" count matches available assets
- [ ] Stats update when switching tabs
- [ ] All tabs still work correctly

### Custodian Dashboard
- [ ] Notification bell appears
- [ ] Settings icon appears
- [ ] Can view and approve requests
- [ ] Statistics cards show correct numbers
- [ ] Filters work correctly

---

## 🔧 Quick Troubleshooting

### Notifications not appearing?
```sql
-- Check if notifications exist
SELECT * FROM notifications WHERE user_id = YOUR_USER_ID ORDER BY created_at DESC LIMIT 5;
```

### Sound not playing?
1. Check preferences: Notifications → Sound enabled?
2. Browser not muted?
3. Open browser console (F12) - any errors?

### Charts not showing?
1. F12 → Console → any JavaScript errors?
2. Check if data exists in database
3. Try hard refresh (Ctrl+F5)

### Stats showing 0?
1. F12 → Network tab → check API calls
2. Verify data exists in database
3. Check JavaScript console for errors

---

## 🎓 Quick Tips

### For Better Notifications:
1. **Enable browser notifications** for desktop alerts
2. **Keep sound on** if you want audio alerts
3. **Customize types** - disable notifications you don't need
4. **Check the bell** regularly for new updates

### For Dashboards:
1. **Hover over charts** to see detailed tooltips
2. **Refresh periodically** to see latest data
3. **Use quick stats** to monitor at a glance
4. **Check activity feed** to see what's happening

---

## 📊 Test Workflow End-to-End

### Complete Request Flow (5 min):

1. **As Employee:**
   - Submit an asset request
   - Check bell - should see notification
   - Check "Pending" stat increases

2. **As Custodian:**
   - Check bell - new notification about request
   - View request details
   - Approve request
   - Check recent activity shows approval

3. **As Admin:**
   - Check bell - new notification
   - View request
   - Final approve
   - Check trend chart updates

4. **As Employee (again):**
   - Check bell - approval notification
   - Check "Approved" stat increases
   - Check "Pending" stat decreases

---

## 🎉 What's New in Each Dashboard?

### Admin Dashboard
| Feature | Description |
|---------|-------------|
| Request Trend Chart | 7-day line chart showing request activity |
| Low Stock Alerts | Widget showing assets with ≤5 quantity |
| Recent Activity | Feed of last 10 system activities |
| Notification Bell | Real-time notification center |
| Settings Icon | Quick access to preferences |

### Employee Dashboard
| Feature | Description |
|---------|-------------|
| My Assets Card | Count of assets assigned to you |
| Pending Card | Count of pending requests |
| Approved Card | Count of approved requests |
| Available Card | Count of assets you can request |
| Notification Bell | Real-time notification center |
| Settings Icon | Quick access to preferences |

### Custodian Dashboard
| Feature | Description |
|---------|-------------|
| Notification Bell | Real-time notification center |
| Settings Icon | Quick access to preferences |
| Enhanced Stats | Real-time approval metrics |
| Quick Approve | One-click approve button |

---

## 🔍 Where to Find Things

### Notification Preferences:
1. Click **gear icon (⚙)** next to notification bell
2. OR navigate to `/notification_preferences.php`

### View All Notifications:
1. Click **bell icon**
2. Click **"View all notifications"** at bottom

### Dashboard Features:
- **Admin**: `/admin/admin_dashboard.php`
- **Employee**: `/employee_dashboard.php`
- **Custodian**: `/custodian/approve_requests.php`

---

## 📞 Need Help?

### Quick Checks:
1. **Browser Console (F12)**: Check for JavaScript errors
2. **Network Tab**: Verify API calls are working
3. **Database**: Verify data exists
4. **Documentation**: Check [ENHANCEMENTS_SUMMARY.md](ENHANCEMENTS_SUMMARY.md)

### Common Issues:
- **Notifications not appearing**: Check database for notifications
- **Sound not playing**: Check browser permissions and preferences
- **Charts blank**: Check Chart.js loaded and data exists
- **Stats showing 0**: Verify API responses in Network tab

---

## ✅ Final Checklist

Before considering testing complete:

- [ ] Tested notifications on all 3 dashboards
- [ ] Created test notification successfully
- [ ] Configured notification preferences
- [ ] Verified sound notifications work
- [ ] Verified browser notifications work
- [ ] Checked all dashboard charts render
- [ ] Verified quick stats update correctly
- [ ] Tested complete workflow (submit → approve → notify)
- [ ] Checked low stock alerts display
- [ ] Verified recent activity feed works
- [ ] Tested on different browsers
- [ ] Tested on mobile/responsive view

---

## 🎊 You're All Set!

Congratulations! You've tested all the new features. The system is now:
- ✅ More interactive with real-time notifications
- ✅ More customizable with user preferences
- ✅ More informative with enhanced dashboards
- ✅ More engaging with visual feedback

**Enjoy the enhanced HCC Asset Management System v2.0!**

---

**Need more details?** Check [ENHANCEMENTS_SUMMARY.md](ENHANCEMENTS_SUMMARY.md)

**Ready to deploy?** See [TESTING_GUIDE.md](TESTING_GUIDE.md)

**Questions?** Review the troubleshooting section above

---

*Last Updated: 2025-11-06*
*Version: 2.0*
