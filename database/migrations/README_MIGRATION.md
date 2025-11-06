# Database Migration Guide
## System Enhancement - Professor's Recommendations

### Migration File
`2025_11_06_comprehensive_system_enhancement.sql`

---

## 🚀 HOW TO RUN THIS MIGRATION IN XAMPP

### Method 1: Using phpMyAdmin (RECOMMENDED)

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Open phpMyAdmin**
   - Go to: http://localhost/phpmyadmin
   - Login (usually no password for root on XAMPP)

3. **Select Database**
   - Click on `hcc_asset_management` database in the left sidebar

4. **Import the Migration**
   - Click on the **"SQL"** tab at the top
   - Open the file: `2025_11_06_comprehensive_system_enhancement.sql`
   - Copy ALL the content
   - Paste it into the SQL query box
   - Click **"Go"** button at the bottom

5. **Check for Success**
   - You should see green checkmarks indicating successful execution
   - If there are errors, scroll down to see error messages

---

### Method 2: Using MySQL Command Line

1. **Open Command Prompt**
   - Press `Win + R`, type `cmd`, press Enter

2. **Navigate to MySQL**
   ```cmd
   cd C:\xampp\mysql\bin
   ```

3. **Login to MySQL**
   ```cmd
   mysql -u root -p
   ```
   - Press Enter (no password by default)

4. **Run the Migration**
   ```sql
   USE hcc_asset_management;
   SOURCE C:\xampp\htdocs\AMS-REQ\database\migrations\2025_11_06_comprehensive_system_enhancement.sql;
   ```

5. **Verify**
   ```sql
   SHOW TABLES;
   DESCRIBE assets;
   SELECT * FROM system_settings;
   ```

---

## ✅ WHAT THIS MIGRATION DOES

### 1. **New Tables Created** (8 tables)
- `notifications` - In-system notification center
- `borrowing_chain` - Track secondary lending
- `asset_movement_logs` - Location tracking history
- `missing_assets_reports` - Report missing/lost items
- `department_approvers` - Department approval hierarchy
- `sms_notifications` - SMS notification logs
- `email_notifications` - Email notification logs
- `system_settings` - System configuration

### 2. **Updated Tables**
- **assets**: Added depreciation fields, updated status enum
- **asset_borrowings**: Added return tracking fields
- **asset_requests**: Added approval workflow columns
- **asset_maintenance**: Added cost and maintenance type
- **users**: Added 'auditor' role

### 3. **New Database Triggers** (3 triggers)
- Auto-calculate return status (On Time/Late)
- Auto-log location changes
- Auto-initialize depreciation values

### 4. **New Views for Reporting** (4 views)
- `view_overdue_borrowings`
- `view_assets_depreciation_status`
- `view_missing_assets_summary`
- `view_department_asset_utilization`

---

## 🔍 VERIFICATION AFTER MIGRATION

Run these queries in phpMyAdmin SQL tab to verify:

```sql
-- Check new tables exist
SHOW TABLES LIKE '%notification%';
SHOW TABLES LIKE '%borrowing_chain%';
SHOW TABLES LIKE '%movement_logs%';

-- Check updated asset status
SHOW COLUMNS FROM assets LIKE 'status';

-- Check new user roles
SHOW COLUMNS FROM users LIKE 'role';

-- Check system settings
SELECT * FROM system_settings;

-- Check views
SHOW FULL TABLES WHERE Table_type = 'VIEW';
```

---

## ⚠️ IMPORTANT NOTES

1. **Backup First!**
   - Before running, backup your database:
   - phpMyAdmin → Export → Go

2. **No Data Loss**
   - This migration only ADDS new fields and tables
   - Existing data remains intact
   - Uses `IF NOT EXISTS` to prevent errors

3. **Rollback Available**
   - If something goes wrong, rollback script is at the bottom of the SQL file
   - Uncomment and run to undo changes

4. **Transaction Safety**
   - Migration runs in a transaction
   - If any error occurs, all changes are rolled back automatically

---

## 🐛 TROUBLESHOOTING

### Error: "Table already exists"
- This is safe! The migration uses `IF NOT EXISTS`
- It will skip creating existing tables

### Error: "Column already exists"
- Also safe! Uses `ADD COLUMN IF NOT EXISTS`

### Error: "Foreign key constraint fails"
- Make sure all related tables exist
- Check that referenced tables have data

### Error: "Syntax error"
- Make sure you copied the ENTIRE SQL file
- Don't run sections individually - run ALL at once

---

## 📊 NEXT STEPS AFTER MIGRATION

1. **Test the Database**
   - Check that all tables were created
   - Verify views are working

2. **Update PHP Code**
   - We'll need to update the application code to use new features
   - This will be done in the next steps

3. **Configure System Settings**
   - Go to system_settings table
   - Adjust values as needed (reminder days, etc.)

---

## 📞 NEED HELP?

If you encounter errors:
1. Take a screenshot of the error message
2. Check which line number caused the error
3. Share the error details

---

## 🎉 SUMMARY

This migration implements ALL the professor's recommendations:
- ✅ Return date tracking with overdue detection
- ✅ Status management (Damaged, Missing, etc.)
- ✅ Approval workflow redesign
- ✅ Notification system (email, SMS, in-system)
- ✅ Barcode enhancement support
- ✅ Depreciation tracking
- ✅ Borrowing chain tracking
- ✅ Missing item reporting
- ✅ Location movement logs
- ✅ Department approvers
- ✅ New auditor role
- ✅ Enhanced reporting views

**Ready to run? Copy the SQL file content into phpMyAdmin and click Go!**
