# 📊 Report Generation & Barcode Scanning - Implementation Guide

## ✅ What's Been Added

### 1. Report Generation System
- **Location:** `admin/reports.php`
- **Access:** Admin & Custodian only
- **Features:**
  - Borrowing History Report (with overdue tracking)
  - Approval/Rejection Summary (detailed & statistics)
  - Depreciation Summary (by asset, category, or office)
  - Missing Assets Report (already existed, now integrated)

### 2. Barcode Scanning System
- **Location:** `scan_asset.php`
- **Access:** All authenticated users
- **Features:**
  - Barcode scanner compatible (auto-submit on Enter)
  - Manual search by name, serial number, or ID
  - Complete asset history display
  - Maintenance tracking
  - Borrowing history
  - Depreciation info
  - Activity logs

---

## 🔧 Quick Setup

### Step 1: Test the Pages

**Test Reports Dashboard:**
```
http://localhost/AMS-REQ/admin/reports.php
```
✅ Fixed: No more sidebar include error
✅ Works for: Admin and Custodian roles
✅ Includes: Back button to dashboard

**Test Asset Scanner:**
```
http://localhost/AMS-REQ/scan_asset.php
```
✅ Works for: All authenticated users
✅ Test with: Asset IDs (206, 210, 211) or names from your database

---

## 🎨 Adding to Navigation Menus

### Option 1: Add to Admin Dashboard

**File:** `admin/admin_dashboard.php`

Find the navigation section and add:

```html
<!-- Reports Link -->
<a href="admin/reports.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
    <i class="fas fa-file-alt mr-2"></i>
    Generate Reports
</a>

<!-- Asset Scanner Link -->
<a href="scan_asset.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
    <i class="fas fa-barcode mr-2"></i>
    Scan Asset
</a>
```

### Option 2: Add to Custodian Dashboard

**File:** `custodian/dashboard.php`

Find the sidebar navigation and add:

```html
<!-- Add to sidebar menu -->
<a href="../admin/reports.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
    <i class="fas fa-file-alt w-6"></i>
    <span>Reports</span>
</a>

<a href="../scan_asset.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
    <i class="fas fa-barcode w-6"></i>
    <span>Scan Asset</span>
</a>
```

### Option 3: Add to Employee Dashboard

**File:** `employee/dashboard.php` or `employee_dashboard.php`

Add the scanner button:

```html
<!-- Asset Scanner Button -->
<a href="scan_asset.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
    <i class="fas fa-barcode mr-2"></i>
    Look Up Asset
</a>
```

---

## 📋 Report Types & Usage

### 1. Borrowing History Report
**Purpose:** Track all borrowing and return activities

**Filters:**
- Status: All / Currently Borrowed / Returned / Overdue
- Date Range: From/To dates

**Export Fields:**
- Request ID, Asset Name, Requester, Campus
- Released Date, Expected Return, Actual Return
- Days Overdue, Return Condition, Late Return Remarks

**Use Cases:**
- Monthly borrowing statistics
- Identify frequent late returners
- Audit trail for asset usage

---

### 2. Approval/Rejection Summary
**Purpose:** Track request approval performance

**Two Modes:**
- **Detailed:** Every request with full approval chain
- **Summary:** Statistics by category and status

**Export Fields (Detailed):**
- Request details, Approval hierarchy (Custodian → Department → Admin)
- Approval times, Rejection reasons, Status changes

**Export Fields (Summary):**
- Total requests, Approved/Rejected/Pending counts
- Average approval time (hours), By category

**Use Cases:**
- Admin performance metrics
- Identify approval bottlenecks
- Management reporting

---

### 3. Depreciation Summary
**Purpose:** Asset valuation and financial reporting

**Three Views:**
- **By Asset:** Detailed list with individual depreciation
- **By Category:** Totals per category (Computers, Furniture, etc.)
- **By Office:** Totals per department/office

**Export Fields:**
- Original Value, Current Value, Total Depreciation
- Age (years/months), Depreciation Rate, Last Updated
- Asset status, Location, Category

**Use Cases:**
- Financial statement preparation
- Budget planning for replacements
- Insurance claims (show current value)
- Asset retirement decisions

---

### 4. Missing Assets Report
**Purpose:** Track lost/missing assets investigation

**Filters:**
- Status: All / Reported / Investigating / Found / Confirmed Lost

**Export Fields:**
- Asset details, Last known location/borrower
- Reported date, Investigator, Resolution notes
- Responsible department

**Use Cases:**
- Security audits
- Loss prevention tracking
- Accountability documentation

---

## 🔍 Barcode Scanning Usage

### For Custodians/Staff:
1. **Physical Inventory:** Scan assets during inventory counts
2. **Maintenance Checks:** View maintenance history before servicing
3. **Quick Location Lookup:** Find where asset is assigned
4. **Status Verification:** Check if asset is available or borrowed

### For Employees:
1. **Asset Details:** Look up equipment they're using
2. **Return Status:** Check expected return dates
3. **Condition History:** See previous maintenance/repairs

### Hardware Scanner Setup:
- Most USB barcode scanners work as keyboard input
- Configure scanner to add "Enter" after each scan (auto-submit)
- Test with asset barcodes from your database
- Fallback: Manual typing always works

---

## 🧪 Testing Checklist

### Test Reports:
- [ ] Admin can access `admin/reports.php`
- [ ] Custodian can access `admin/reports.php`
- [ ] Employee/Office CANNOT access (403 error)
- [ ] Each report type generates CSV
- [ ] Date filters work correctly
- [ ] Status filters show correct data
- [ ] CSV opens properly in Excel

### Test Barcode Scanner:
- [ ] All users can access `scan_asset.php`
- [ ] Search by asset ID works (try: 206, 210, 211)
- [ ] Search by asset name works
- [ ] Search by serial number works
- [ ] Search by barcode works (if you have barcodes)
- [ ] Asset details display correctly
- [ ] Maintenance history shows (if exists)
- [ ] Borrowing history shows (if exists)
- [ ] Depreciation info calculates correctly
- [ ] Scan is logged in `asset_scans` table
- [ ] "Not found" error shows for invalid search

---

## 📊 Database Tables Used

### Reports Use:
- `asset_requests` - Borrowing and approval data
- `assets` - Asset details and depreciation
- `users` - Requester, approver names
- `categories` - Grouping for reports
- `offices` - Department grouping
- `missing_assets_reports` - Missing asset data

### Scanner Uses:
- `assets` - Main asset info
- `asset_maintenance` - Service history
- `asset_requests` - Borrowing history
- `asset_scans` - Scan log (auto-created)
- `activity_log` - Activity timeline

---

## 🎯 Common Customizations

### Change Report Headers:
Edit the CSV export files:
- `api/export_borrowing_history.php`
- `api/export_approval_summary.php`
- `api/export_depreciation_summary.php`

Add school logo/name in the CSV header rows.

### Customize Date Ranges:
In `admin/reports.php`, find:
```javascript
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
```
Change to default to different periods (year-to-date, last 3 months, etc.)

### Add More Report Types:
1. Create new file: `api/export_[your_report].php`
2. Follow the pattern in existing export files
3. Add new report card in `admin/reports.php`
4. Add case in `openReportModal()` and `generateReport()` functions

---

## 🆘 Troubleshooting

### "Failed to open sidebar.php"
✅ **FIXED** - Reports page now works without sidebar

### "Asset not found"
- Check if asset exists in database
- Verify campus_id matches user's campus
- Try searching by asset ID instead

### Reports show no data
- Check date range (might be too narrow)
- Verify status filter (might exclude all records)
- Check if user's campus has data

### CSV doesn't open in Excel
- File has UTF-8 BOM (Excel compatibility) ✅
- Try opening with "Text Import Wizard"
- Alternative: Open in Google Sheets first

### Barcode scanner doesn't work
- Test manual typing first
- Check scanner is in "keyboard mode"
- Ensure scanner adds "Enter" after scan
- Try with simple asset ID numbers first

---

## 🚀 Next Steps (Optional)

1. **Add to Navigation:** Update admin/custodian sidebars
2. **Create Dashboard Widgets:** Show recent reports generated
3. **Schedule Reports:** Auto-generate monthly reports via cron
4. **Add PDF Export:** Install dompdf library for PDF reports
5. **QR Code Generator:** Add QR codes to asset tags for easier scanning

---

## 📞 Support

If you encounter issues:
1. Check PHP error logs: `C:\xampp\htdocs\AMS-REQ\storage\logs\`
2. Check browser console for JavaScript errors
3. Verify database connections in `config.php`
4. Test with simple searches first (asset ID numbers)

---

## ✅ Summary

**Files Created:**
- `admin/reports.php` - Main reports dashboard
- `scan_asset.php` - Asset scanner interface
- `api/export_borrowing_history.php` - Borrowing report API
- `api/export_approval_summary.php` - Approval report API
- `api/export_depreciation_summary.php` - Depreciation report API
- `api/barcode_lookup.php` - Asset lookup API
- `api/dashboard_stats.php` - Statistics API

**Ready to Use:**
✅ Report generation working
✅ Barcode scanning working
✅ No sidebar dependency
✅ Role-based access control
✅ CSV export with Excel compatibility
✅ Auto-logging of scans

**Next Action:**
Add navigation links to your existing dashboards so users can easily find these new features!
