# Auto Scheduler API Guide

## 🎯 Overview

This API allows you to trigger the email notification scheduler remotely without Windows Task Scheduler. Perfect for:
- Web-based cron services
- Cloud hosting environments
- Remote automation
- Testing and monitoring

---

## 🔐 API Endpoint

**URL:** `http://your-domain.com/AMS-REQ/api/scheduler_trigger.php`

**Method:** GET or POST

**Authentication:** API Key required

**API Key:** `hcc_scheduler_api_2024_secure`

⚠️ **IMPORTANT:** Change the API key in production! Edit `api/scheduler_trigger.php` line 17

---

## 📝 Usage Examples

### **Local Testing (Browser)**
```
http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure
```

### **cURL Command**
```bash
curl "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
```

### **PowerShell**
```powershell
Invoke-WebRequest -Uri "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
```

### **JavaScript/Fetch**
```javascript
fetch('http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure')
  .then(response => response.json())
  .then(data => console.log(data));
```

### **PHP**
```php
$response = file_get_contents('http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure');
$result = json_decode($response, true);
print_r($result);
```

---

## 📊 API Response

### **Success Response (200 OK)**
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
  "client_ip": "127.0.0.1",
  "output_preview": "=== Asset Notification Scheduler Started ===..."
}
```

### **Error Response (401 Unauthorized)**
```json
{
  "success": false,
  "error": "Unauthorized - Invalid API key",
  "timestamp": "2025-01-08 14:30:15"
}
```

### **Error Response (500 Internal Server Error)**
```json
{
  "success": false,
  "error": "Scheduler file not found",
  "timestamp": "2025-01-08 14:30:15"
}
```

---

## 🌐 Web-Based Cron Services

### **1. cron-job.org** ⭐ (Recommended - Free)

**Setup:**
1. Go to https://cron-job.org
2. Create free account
3. Click "Create Cronjob"
4. Configure:
   - **Title:** HCC Asset Notifications
   - **URL:** `http://your-domain.com/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure`
   - **Schedule:** Daily at 8:00 AM
   - **Timezone:** Your timezone
   - **Notification:** Email on failure (optional)
5. Save and enable

**Limits:**
- Free tier: 60 executions/month
- Perfect for daily notifications

---

### **2. EasyCron** (Free tier available)

**Setup:**
1. Go to https://www.easycron.com
2. Create account
3. Add cron job:
   - **URL:** Your API endpoint with key
   - **Cron Expression:** `0 8 * * *` (8 AM daily)
   - **Time Zone:** Your timezone
4. Test and enable

**Limits:**
- Free: Up to 1 job
- Execution logs available

---

### **3. cPanel Cron Jobs** (If hosted on cPanel)

**Setup:**
1. Login to cPanel
2. Go to "Cron Jobs"
3. Add new cron job:
   - **Minute:** 0
   - **Hour:** 8
   - **Day:** *
   - **Month:** *
   - **Weekday:** *
   - **Command:**
     ```bash
     curl "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
     ```

---

### **4. UptimeRobot** (Free monitoring + trigger)

**Setup:**
1. Go to https://uptimerobot.com
2. Create monitor:
   - **Monitor Type:** HTTP(s)
   - **URL:** Your API endpoint
   - **Monitoring Interval:** 1 day (maximum on free)
3. Enable and save

**Note:** UptimeRobot is primarily for monitoring but triggers URL on each check

---

### **5. GitHub Actions** (Free for public/private repos)

Create `.github/workflows/daily-notifications.yml`:

```yaml
name: Daily Asset Notifications

on:
  schedule:
    - cron: '0 8 * * *'  # 8 AM UTC daily
  workflow_dispatch:  # Manual trigger

jobs:
  trigger-notifications:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger Scheduler
        run: |
          curl -X GET "http://your-domain.com/AMS-REQ/api/scheduler_trigger.php?api_key=${{ secrets.SCHEDULER_API_KEY }}"
```

**Setup:**
1. Create repository
2. Add secret: `SCHEDULER_API_KEY` with your key
3. Push workflow file
4. Auto-runs daily at 8 AM UTC

---

### **6. Zapier** (Scheduled Zaps - Paid)

**Setup:**
1. Create new Zap
2. Trigger: Schedule by Zapier
3. Frequency: Every Day at 8:00 AM
4. Action: Webhooks by Zapier
5. URL: Your API endpoint with key
6. Method: GET
7. Test and turn on

**Cost:** Requires paid plan for scheduled tasks

---

### **7. IFTTT** (Free with limitations)

**Setup:**
1. Create applet
2. Trigger: Date & Time → Every day at
3. Time: 8:00 AM
4. Action: Webhooks → Make a web request
5. URL: Your API endpoint
6. Method: GET
7. Save

---

## 🖥️ Local Automation Options

### **Option 1: Windows Task Scheduler (Recommended)**

Create task that calls API via PowerShell:

**PowerShell Script:** `call_api_scheduler.ps1`
```powershell
$url = "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
$response = Invoke-RestMethod -Uri $url -Method Get
Write-Output $response | ConvertTo-Json
```

**Task Scheduler:**
- Program: `powershell.exe`
- Arguments: `-File "C:\path\to\call_api_scheduler.ps1"`
- Trigger: Daily at 8 AM

---

### **Option 2: Node.js Cron Job**

Install node-cron:
```bash
npm install node-cron axios
```

Create `scheduler.js`:
```javascript
const cron = require('node-cron');
const axios = require('axios');

// Run every day at 8 AM
cron.schedule('0 8 * * *', async () => {
  try {
    const response = await axios.get(
      'http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure'
    );
    console.log('Scheduler triggered:', response.data);
  } catch (error) {
    console.error('Error:', error.message);
  }
});

console.log('Cron job started - will run daily at 8 AM');
```

Run continuously:
```bash
node scheduler.js
```

Or use PM2 to run in background:
```bash
npm install -g pm2
pm2 start scheduler.js
pm2 save
pm2 startup
```

---

### **Option 3: Python Script**

```python
import requests
import schedule
import time

def trigger_scheduler():
    url = "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
    try:
        response = requests.get(url)
        print("Response:", response.json())
    except Exception as e:
        print("Error:", str(e))

# Schedule daily at 8 AM
schedule.every().day.at("08:00").do(trigger_scheduler)

print("Scheduler running... Press Ctrl+C to stop")
while True:
    schedule.run_pending()
    time.sleep(60)
```

---

## 🔒 Security Best Practices

### **1. Change the API Key**
Edit `api/scheduler_trigger.php` line 17:
```php
define('API_SECRET_KEY', 'your_random_secure_key_here');
```

Generate secure key:
```php
echo bin2hex(random_bytes(32));
```

---

### **2. Enable IP Whitelist** (Optional)

Edit `api/scheduler_trigger.php` line 18:
```php
define('ALLOWED_IPS', ['123.456.789.0', '192.168.1.100']);
```

Uncomment lines 32-43 to enable IP checking.

---

### **3. Use HTTPS**

When deployed online, always use HTTPS:
```
https://your-domain.com/AMS-REQ/api/scheduler_trigger.php?api_key=...
```

---

### **4. Environment Variables**

Store API key in environment variable instead of URL:

**PHP Script:**
```php
$apiKey = getenv('SCHEDULER_API_KEY');
$url = "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=" . $apiKey;
```

---

## 📊 Monitoring & Logs

### **API Call Logs**

Location: `C:\xampp\htdocs\AMS-REQ\logs\api_scheduler_calls.log`

Example:
```
[2025-01-08 08:00:01] API called from IP: 127.0.0.1 | Method: GET
[2025-01-08 08:00:15] API called from IP: 192.168.1.100 | Method: POST
```

---

### **Scheduler Execution Logs**

Location: `C:\xampp\htdocs\AMS-REQ\logs\notification_scheduler_YYYY-MM-DD.log`

---

### **Monitor API Health**

Create monitoring endpoint:
```
http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=YOUR_KEY&test=1
```

---

## 🧪 Testing

### **1. Test API Response**
```bash
curl -i "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
```

### **2. Test Without API Key (Should fail)**
```bash
curl "http://localhost/AMS-REQ/api/scheduler_trigger.php"
```

### **3. Test Wrong API Key (Should fail)**
```bash
curl "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=wrong_key"
```

### **4. Check Response Time**
```bash
time curl "http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure"
```

---

## 🚀 Quick Start

### **Fastest Setup (5 minutes)**

1. **Test the API:**
   ```
   http://localhost/AMS-REQ/api/scheduler_trigger.php?api_key=hcc_scheduler_api_2024_secure
   ```

2. **Sign up for cron-job.org** (Free)

3. **Create cron job:**
   - URL: Your API endpoint
   - Schedule: Daily at 8 AM
   - Enable

4. **Done!** Notifications will send automatically

---

## 🆘 Troubleshooting

| Issue | Solution |
|-------|----------|
| **401 Unauthorized** | Check API key is correct |
| **403 Forbidden** | Check IP whitelist settings |
| **500 Error** | Check scheduler file exists |
| **No notifications sent** | Check logs in `/logs/` folder |
| **Timeout** | Increase PHP `max_execution_time` |

---

## 📚 Additional Resources

- [cron-job.org Documentation](https://cron-job.org/en/documentation/)
- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [PHP cURL Documentation](https://www.php.net/manual/en/book.curl.php)

---

**Created:** January 8, 2025
**Version:** 1.0
**API File:** `api/scheduler_trigger.php`
