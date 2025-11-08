# 🚀 Auto Scheduler API - Quick Setup

## ✅ What Was Created

I've created an **API-based auto-scheduler** that can be triggered by web-based cron services, eliminating the need for Windows Task Scheduler!

---

## 📁 New Files Created

1. **`/api/scheduler_trigger.php`** - Main API endpoint
2. **`/api/SCHEDULER_API_GUIDE.md`** - Complete documentation
3. **`/api/test_scheduler_api.html`** - Interactive testing tool

---

## 🧪 Test It Now!

### **Option 1: Browser Test Page** (Easiest)
```
http://localhost/AMS-REQ/api/test_scheduler_api.html
```

Click the **"✅ Test API"** button to verify it works!

---

### **Option 2: Direct API Call**
```
http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure
```

You should see JSON response with statistics.

---

## 🌐 Set Up Free Auto-Scheduler (5 minutes)

### **Using cron-job.org** (Recommended - 100% Free)

1. **Go to:** https://cron-job.org

2. **Create free account**

3. **Add new cron job:**
   - Title: `HCC Asset Notifications`
   - URL: `http://your-domain.com/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure`
   - Schedule: Daily at 8:00 AM
   - Time zone: Your timezone

4. **Enable and save**

5. **Done!** ✅ Emails will send automatically every day

---

## 🔐 Security Settings

### **Change API Key (Important!)**

Edit: `/api/scheduler_trigger.php` (Line 17)

```php
define('API_SECRET_KEY', 'your_new_secure_key_here');
```

Generate secure key:
```php
echo bin2hex(random_bytes(32));
```

---

## 📊 How It Works

```
External Service (cron-job.org)
        ↓
Daily at 8:00 AM
        ↓
Calls API: /api/scheduler_trigger.php
        ↓
API verifies key
        ↓
Executes notification scheduler
        ↓
Sends emails (2-day warnings + overdue alerts)
        ↓
Returns JSON response with statistics
```

---

## 🎯 Available Services

| Service | Free Tier | Best For |
|---------|-----------|----------|
| **cron-job.org** | 60 jobs/month | Daily notifications ⭐ |
| **EasyCron** | 1 job | Simple setup |
| **GitHub Actions** | Unlimited | Code-based automation |
| **UptimeRobot** | 50 monitors | Monitoring + trigger |

See full guide: [SCHEDULER_API_GUIDE.md](api/SCHEDULER_API_GUIDE.md)

---

## 📝 API Usage Examples

### **cURL**
```bash
curl "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
```

### **PowerShell**
```powershell
Invoke-WebRequest -Uri "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
```

### **JavaScript**
```javascript
fetch('http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure')
  .then(r => r.json())
  .then(data => console.log(data));
```

---

## 📈 Success Response Example

```json
{
  "success": true,
  "message": "Scheduler executed successfully",
  "statistics": {
    "two_day_reminders": 3,
    "overdue_alerts": 2,
    "errors": 0
  },
  "execution_time": "2025-01-08 14:30:15",
  "client_ip": "127.0.0.1"
}
```

---

## 🔍 Monitoring

### **API Call Logs**
```
C:\xampp\htdocs\AMS-REQ\logs\api_scheduler_calls.log
```

### **Execution Logs**
```
C:\xampp\htdocs\AMS-REQ\logs\notification_scheduler_YYYY-MM-DD.log
```

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| 401 Unauthorized | Check API key is correct |
| 500 Error | Verify scheduler file exists |
| No emails sent | Check notification logs |
| Timeout | Increase PHP max_execution_time |

---

## 🎉 Benefits Over Windows Task Scheduler

✅ Works on any hosting (shared, cloud, VPS)
✅ No Windows required
✅ Easy remote monitoring
✅ RESTful API approach
✅ Multiple trigger options
✅ Cloud-ready architecture
✅ Better for production environments

---

## 📚 Complete Documentation

For advanced usage, see:
- **Full API Guide:** [SCHEDULER_API_GUIDE.md](api/SCHEDULER_API_GUIDE.md)
- **Test Tool:** [test_scheduler_api.html](api/test_scheduler_api.html)

---

## ⚡ Quick Start Checklist

- [ ] Test API: `http://localhost/AMS-REQ/api/test_scheduler_api.html`
- [ ] Change API key in production
- [ ] Sign up for cron-job.org (free)
- [ ] Create daily cron job
- [ ] Test with manual trigger
- [ ] Monitor logs for first few days
- [ ] Enjoy automated notifications! 🎉

---

**Created:** January 8, 2025
**Version:** 1.0
**Status:** ✅ Ready for Production
