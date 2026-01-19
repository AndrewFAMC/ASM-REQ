# Quick Scan Update Feature - User Guide

## Overview
The **Quick Scan Update** feature allows custodians to rapidly scan assets using barcode/QR scanners and instantly update their status, location, and notes. This feature is optimized for physical inventory checks and field updates.

## Access
- **URL**: `/custodian/quick_scan_update.php`
- **Required Role**: Custodian or Admin
- **Navigation**: Available in the custodian sidebar menu (marked with "NEW" badge)

---

## Features

### 1. **Real-time Scanner Integration**
- ✅ USB barcode scanner support (Web USB API)
- ✅ Bluetooth scanner support (Web Bluetooth API)
- ✅ Keyboard emulation mode (standard scanners)
- ✅ Auto-detection of scanner connection
- ✅ Visual connection status indicator

### 2. **Quick Actions**
After scanning an asset, you can instantly:
- **Update Status**: Available, In Use, Under Repair, Damaged
- **Update Location**: Change asset location with one click
- **Add Notes**: Append timestamped notes/remarks
- **View Full Details**: Open complete asset information in new tab

### 3. **Session Statistics**
Real-time tracking of:
- Scanned Today (total scans from all users)
- Updated (successful updates in this session)
- This Session (scans in current session)
- Avg. Time (average scan processing time)

### 4. **Scan History**
- Visual history of last 10 scans in current session
- Shows asset name, barcode/serial, status, and timestamp
- Color-coded for easy identification

---

## How to Use

### Basic Workflow

1. **Open Quick Scan Update Page**
   - Navigate to: Custodian Dashboard → Quick Scan Update
   - Or directly access: `http://localhost/AMS-REQ/custodian/quick_scan_update.php`

2. **Connect Your Scanner** (Optional)
   - USB scanners: Plug in and wait for "Scanner: Connected" status
   - Bluetooth scanners: Click scanner status to manually pair
   - Keyboard mode: Works automatically with any scanner

3. **Scan an Asset**
   - Point scanner at barcode/QR code
   - Scanner automatically sends input and searches
   - Or manually type barcode/serial/name and press Enter

4. **Asset Details Appear**
   - View asset name, status, location, and current borrowing info
   - See color-coded status badge
   - Review asset details before updating

5. **Perform Quick Action**
   - Click any status button to change asset status
   - Click "Update Location" to change location
   - Click "Add Notes" to append remarks
   - Confirm the action in the popup

6. **Continue Scanning**
   - After update, panel auto-hides after 1.5 seconds
   - Focus returns to search input
   - Ready for next scan immediately

---

## Status Update Options

### Available Status Changes:
- **Available** (Green) - Asset is ready for use/borrowing
- **In Use** (Blue) - Asset is currently being used
- **Under Repair** (Yellow) - Asset needs maintenance
- **Damaged** (Red) - Asset is damaged and needs attention

### Additional Actions:
- **Update Location**: Change asset's physical location
- **Add Notes**: Add timestamped observations or remarks
- **View Full Details**: Open complete asset page with history

---

## Scanner Hardware Setup

### Recommended Hardware:
1. **USB Barcode Scanners**
   - Any HID-compliant USB barcode scanner
   - No driver installation needed (keyboard emulation)
   - Plug-and-play compatibility

2. **Bluetooth Scanners**
   - Scanners with standard Bluetooth profiles
   - Pair via browser's Bluetooth dialog
   - Battery-powered for mobile scanning

### Configuration Tips:
- Set scanner to send "Enter" after each scan (default on most scanners)
- Minimum barcode length: 4 characters
- Maximum barcode length: 50 characters
- Scan time threshold: 100ms between characters

---

## Use Cases

### 1. **Physical Inventory Walkthrough**
**Scenario**: Custodian walks through campus checking assets

**Steps**:
1. Open Quick Scan Update on mobile device/tablet
2. Scan each asset encountered
3. Update status if needed (e.g., "Under Repair" for damaged items)
4. Add notes about condition
5. Update location if asset moved

**Benefits**: Complete inventory check in minutes, instant updates

---

### 2. **Outdoor Asset Status Check**
**Scenario**: Check furniture outside building

**Steps**:
1. Scan chair/table barcode
2. View current status and borrowing info
3. If damaged, update status to "Damaged"
4. Add notes: "Needs replacement - weathered"
5. Continue to next asset

**Benefits**: Real-time status updates without returning to office

---

### 3. **Rapid Location Updates**
**Scenario**: Assets relocated during renovation

**Steps**:
1. Scan asset in new location
2. Click "Update Location"
3. Enter new location (e.g., "Room 205")
4. Confirm
5. Repeat for all relocated assets

**Benefits**: Accurate location tracking, easy to find assets later

---

## Auto-Logging

Every scan and update is automatically logged:

### Scan Logs (`asset_scans` table):
- **Type**: Status Check, Location Update
- **Scanned By**: Custodian name
- **Notes**: Description of action
- **Timestamp**: Exact scan time

### Activity Logs (`activity_log` table):
- **Action**: status_update, location_update, notes_added
- **Description**: Full details of change (old → new values)
- **Performed By**: Custodian name
- **Timestamp**: Action time

---

## API Endpoint

### Endpoint: `/api/quick_asset_update.php`

**Methods Supported**:

#### 1. Update Status
```json
POST /api/quick_asset_update.php
{
    "action": "update_status",
    "asset_id": 123,
    "status": "Available",
    "csrf_token": "..."
}
```

#### 2. Update Location
```json
POST /api/quick_asset_update.php
{
    "action": "update_location",
    "asset_id": 123,
    "location": "Room 205",
    "csrf_token": "..."
}
```

#### 3. Add Notes
```json
POST /api/quick_asset_update.php
{
    "action": "add_notes",
    "asset_id": 123,
    "notes": "Observed during inventory check - good condition",
    "csrf_token": "..."
}
```

**Response Format**:
```json
{
    "success": true,
    "message": "Status updated successfully",
    "new_status": "Available",
    "old_status": "In Use"
}
```

---

## Security Features

1. **Authentication Required**
   - Must be logged in as Custodian or Admin
   - Session validation on every request

2. **CSRF Protection**
   - CSRF token required for all updates
   - Token validation on server side

3. **Campus Isolation**
   - Can only update assets in your campus
   - Cross-campus updates blocked

4. **Audit Trail**
   - All actions logged with user name and timestamp
   - Complete history in activity_log and asset_scans tables

---

## Keyboard Shortcuts

- **Enter**: Submit search / scan
- **Focus**: Search input auto-focused on page load
- **Tab**: Navigate between action buttons

---

## Browser Compatibility

### Fully Supported:
- ✅ Chrome/Edge (Web USB, Web Bluetooth)
- ✅ Firefox (Keyboard mode, limited Bluetooth)
- ✅ Safari (Keyboard mode)

### Scanner Modes by Browser:
- **Chrome/Edge**: Full hardware integration (USB + Bluetooth)
- **Firefox**: Keyboard emulation only
- **Safari**: Keyboard emulation only

**Recommendation**: Use Chrome or Edge for best hardware scanner support

---

## Mobile Usage

### Responsive Design:
- Works on tablets and mobile devices
- Touch-optimized buttons
- Large input fields for easy scanning

### Mobile Scanner Support:
- Bluetooth scanners recommended for mobile
- Camera-based QR scanners (future enhancement)
- Manual entry always available

---

## Troubleshooting

### Scanner Not Connecting:
1. Check USB cable/Bluetooth pairing
2. Verify scanner is in HID mode
3. Try keyboard emulation mode (works with all scanners)
4. Refresh page and rescan

### Asset Not Found:
1. Verify barcode/serial number is correct
2. Check if asset exists in your campus
3. Try searching by asset name or ID
4. Contact admin if asset should exist

### Update Failed:
1. Verify you have custodian permissions
2. Check internet connection
3. Ensure asset is not locked by another process
4. Review error message for specific issue

### Slow Performance:
1. Clear browser cache
2. Reduce number of browser tabs
3. Check network connection
4. Contact IT if persistent

---

## Tips for Efficient Scanning

1. **Pre-print Barcodes**: Ensure all assets have readable barcodes
2. **Clean Scanner Lens**: Keep scanner lens clean for accurate reads
3. **Proper Distance**: Hold scanner 4-12 inches from barcode
4. **Good Lighting**: Scan in well-lit areas
5. **Battery Check**: Keep Bluetooth scanners charged
6. **Regular Breaks**: Take breaks during long scanning sessions
7. **Verify Updates**: Check scan history to confirm updates

---

## Future Enhancements

Planned features:
- 📷 Camera-based QR code scanning (mobile)
- 📊 Export scan session reports
- 🔔 Audio feedback on successful scan
- 📱 Progressive Web App (PWA) for offline scanning
- 🏷️ Bulk status updates
- 📍 GPS location capture for outdoor assets

---

## Support

For assistance with Quick Scan Update:
- **Technical Issues**: Contact IT Support
- **Scanner Hardware**: Contact Equipment Services
- **Feature Requests**: Submit to Asset Management Team

---

## Related Documentation

- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) - System overview
- [Asset Scanning Guide](../scan_asset.php) - General asset lookup
- [Tag Printing Guide](MISSING_ASSET_REPORTING_IMPLEMENTATION.md) - Printing asset tags

---

**Last Updated**: 2025-01-11
**Feature Version**: 1.0
**Compatibility**: HCC Asset Management System v2.0+
