# Asset Transfer Tracking System - Implementation Guide

## Overview

The Asset Transfer Tracking System allows custodians to record and monitor the chain of custody when assets are transferred between borrowers. This feature addresses the scenario where an asset borrowed by Person A is later given to Person B, and eventually returned by Person B or even Person C.

## ✅ What Was Implemented

### 1. **Database Foundation**
The `borrowing_chain` table already existed with perfect schema:
```sql
CREATE TABLE borrowing_chain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrowing_id INT,
    asset_id INT NOT NULL,
    from_person VARCHAR(255) NOT NULL,
    to_person VARCHAR(255) NOT NULL,
    to_person_contact VARCHAR(255),
    transfer_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE,
    actual_return_date DATETIME,
    status ENUM('active', 'returned') DEFAULT 'active',
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. **API Endpoint** (`api/transfer_asset.php`)

#### Available Actions:

**A. Record Transfer**
```javascript
POST /api/transfer_asset.php
{
    "action": "record_transfer",
    "request_id": 123,
    "from_person": "John Doe",
    "to_person": "Jane Smith",
    "to_person_contact": "09123456789",
    "transfer_date": "2025-11-09 14:30:00",
    "notes": "Transfer for field work"
}
```

**B. Get Transfer Chain**
```javascript
GET /api/transfer_asset.php?action=get_transfer_chain&request_id=123
```

**C. Get Active Borrowing**
```javascript
GET /api/transfer_asset.php?action=get_active_borrowing&request_id=123
```

**D. Verify Borrower**
```javascript
POST /api/transfer_asset.php
{
    "action": "verify_borrower",
    "request_id": 123,
    "returning_person": "Richard Santos"
}
```

### 3. **Frontend Features** (`custodian/return_assets.php`)

#### Three New Buttons for Each Asset:
1. **Record Transfer** - Manually record asset transfers between people
2. **Transfer History** - View complete chain of custody
3. **Process Return** - Enhanced with automatic indirect return detection

### 4. **Automatic Indirect Return Detection**

When a custodian processes a return, the system now:

**Step 1: Identify Who's Returning**
- Asks custodian to confirm who is physically returning the asset
- Pre-fills with original borrower name
- Allows modification if someone else is returning

**Step 2: Detect Mismatch**
- Compares returning person vs. original borrower
- If different, shows "Indirect Return Detected" warning
- Offers two options:
  - **Record Transfer & Continue** - Opens transfer form to document chain
  - **Skip Transfer Recording** - Proceeds with return but notes indirect return

**Step 3: Process Return**
- If indirect return, adds yellow notice banner
- All standard return processing continues (overdue check, condition, notes)
- Logs indirect return in activity log

---

## 🎯 Use Cases Solved

### Scenario 1: Direct Transfer Chain
**Situation:** Richard borrowed a laptop, gave it to Sarah for a presentation, Sarah returns it.

**Workflow:**
1. Sarah brings laptop to custodian
2. Custodian clicks "Process Return"
3. System asks "Who is returning this asset?"
4. Custodian enters "Sarah"
5. System detects: Original borrower = Richard, Returning person = Sarah
6. Shows "Indirect Return Detected" alert
7. Custodian clicks "Record Transfer & Continue"
8. Transfer form opens:
   - From: Richard
   - To: Sarah
   - Contact: (Sarah's number)
   - Notes: "Borrowed for presentation"
9. Clicks "Record Transfer"
10. Transfer saved to `borrowing_chain` table
11. Custodian clicks "Process Return" again
12. Completes return normally

**Result:** Full chain of custody recorded: Richard → Sarah → Returned

---

### Scenario 2: Multiple Transfers
**Situation:** Richard → Sarah → Miguel → Returned by Miguel

**Workflow:**
1. First transfer (Richard → Sarah):
   - Custodian clicks "Record Transfer" on asset card
   - From: Richard, To: Sarah
   - Records transfer #1

2. Second transfer (Sarah → Miguel):
   - Later, custodian records another transfer
   - From: Sarah, To: Miguel
   - Records transfer #2

3. Return by Miguel:
   - Miguel returns laptop
   - Custodian processes return
   - System notes: Original = Richard, Returning = Miguel
   - Custodian can view full history showing 2 transfers

**Result:** Complete audit trail maintained

---

### Scenario 3: View Transfer History
**Workflow:**
1. Click "Transfer History" button on any active asset
2. Modal displays:
   - Total number of transfers
   - Each transfer card showing:
     - Transfer number and date
     - FROM person (red background)
     - TO person (green background)
     - Contact information
     - Notes
     - Who recorded it
     - Status (Active/Completed)

---

## 🔧 Technical Implementation Details

### Frontend JavaScript Methods

```javascript
class ReturnManager {
    // Record new transfer
    async recordTransfer(requestId) { }

    // Submit transfer data
    async submitTransfer(requestId) { }

    // View transfer history
    async viewTransferHistory(requestId) { }

    // Enhanced return with detection
    async returnAsset(requestId) { }
}
```

### Key Features:

**1. CSRF Protection**
All transfer operations use CSRF tokens for security.

**2. Activity Logging**
Every transfer is logged:
```php
logActivity(
    $pdo,
    $assetId,
    'ASSET_TRANSFERRED',
    "Asset transferred from '{$fromPerson}' to '{$toPerson}'. Transfer ID: #{$transferId}"
);
```

**3. Last Known Borrower Tracking**
```php
UPDATE asset_borrowings
SET last_known_borrower = ?
WHERE id = ?
```

**4. Notification System**
Creates in-app notification when transfer is recorded.

---

## 📋 Database Schema Changes

**No changes needed!** The `borrowing_chain` table already existed. Now it's being used.

**Fields Used:**
- `asset_id` - Links to the asset
- `from_person` - Who transferred it
- `to_person` - Who received it
- `to_person_contact` - Contact info for new holder
- `transfer_date` - When transfer happened
- `status` - 'active' or 'returned'
- `notes` - Reason for transfer
- `recorded_by` - Custodian who recorded it

---

## 🎨 UI Components

### 1. Transfer Modal
**Features:**
- Asset information banner (blue)
- From Person field (pre-filled)
- To Person field (required)
- Contact number field
- Transfer date picker
- Notes textarea
- Cancel & Submit buttons

### 2. Transfer History Modal
**Features:**
- Header with transfer count
- Scrollable list of transfers
- Color-coded FROM (red) and TO (green) boxes
- Status badges (Active = green, Completed = gray)
- Numbered transfer cards (#1, #2, etc.)
- Recorded by information

### 3. Indirect Return Alert
**Features:**
- Orange warning banner
- Shows mismatch between original and returning person
- Three-button choice:
  - Record Transfer & Continue
  - Skip Transfer Recording
  - Cancel Return

---

## 🔐 Security & Permissions

**Required Role:** Custodian or Admin only

**CSRF Protection:** ✅ Enabled on all POST endpoints

**Input Validation:**
- Request ID must be valid integer
- From/To persons required (non-empty strings)
- Transfer date validated
- SQL injection protected (prepared statements)

---

## 📊 Reporting & Analytics

### Available Data Points:
1. Total transfers per asset
2. Current holder (last to_person)
3. Transfer frequency
4. Chain length (how many hands)
5. Time between transfers

### SQL Queries:

**Get all transfers for an asset:**
```sql
SELECT * FROM borrowing_chain
WHERE asset_id = ?
ORDER BY transfer_date DESC;
```

**Get assets with most transfers:**
```sql
SELECT asset_id, COUNT(*) as transfer_count
FROM borrowing_chain
GROUP BY asset_id
ORDER BY transfer_count DESC;
```

**Find current holder:**
```sql
SELECT to_person
FROM borrowing_chain
WHERE asset_id = ? AND status = 'active'
ORDER BY transfer_date DESC
LIMIT 1;
```

---

## 🚀 Usage Instructions

### For Custodians:

**Recording a Transfer:**
1. Navigate to "Return Assets" page
2. Find the asset that was transferred
3. Click "Record Transfer" button
4. Fill in transfer details:
   - Verify "From Person" (auto-filled with last known holder)
   - Enter "To Person" (new holder)
   - Add contact number (optional but recommended)
   - Select transfer date/time
   - Add notes explaining why transfer occurred
5. Click "Record Transfer"
6. Success notification appears

**Viewing Transfer History:**
1. Find any asset on Return Assets page
2. Click "Transfer History" button
3. View complete chain of custody
4. See who recorded each transfer
5. Check active vs. completed status

**Processing Returns with Indirect Detection:**
1. Click "Process Return"
2. System asks "Who is returning this asset?"
3. Enter name of person physically returning it
4. If not original borrower:
   - System shows warning
   - Choose to record transfer or skip
5. Complete return process normally

---

## 🐛 Troubleshooting

### Issue: "Borrowing record not found"
**Cause:** Invalid request ID or borrowing ID
**Solution:** Verify the asset is in "released" status

### Issue: Transfer not appearing in history
**Cause:** May be looking at wrong request/asset
**Solution:** Check asset_id matches in database

### Issue: Can't record transfer
**Cause:** Permission denied or CSRF token expired
**Solution:**
- Verify custodian role
- Refresh page to get new CSRF token

---

## 📈 Future Enhancements (Optional)

### Potential Additions:
1. **Email Notifications** - Notify original borrower when asset is transferred
2. **Transfer Approval Workflow** - Require approval before transfers
3. **QR Code Scanning** - Scan asset during transfer for verification
4. **Transfer Limits** - Set max number of transfers per asset
5. **Auto-expire Transfers** - Mark transfers as complete after certain period
6. **Transfer Reports** - Generate PDF reports of transfer chains
7. **Mobile App Integration** - Record transfers via mobile device

---

## 📝 Testing Checklist

- [ ] Record a simple transfer (A → B)
- [ ] View transfer history
- [ ] Process return by different person
- [ ] Test indirect return detection
- [ ] Record multiple transfers (A → B → C)
- [ ] Skip transfer recording option
- [ ] Check activity logs
- [ ] Verify CSRF protection
- [ ] Test with non-custodian user (should fail)
- [ ] Check database records in `borrowing_chain`

---

## 💡 Benefits

### ✅ Accountability
- Know exactly who had the asset at any time
- Clear chain of custody for auditing

### ✅ Traceability
- Track assets through multiple hands
- Identify where assets might be if missing

### ✅ Compliance
- Meet institutional requirements for asset tracking
- Provide audit trail for high-value items

### ✅ Prevention
- Discourage unauthorized transfers
- Make users aware transfers are tracked

### ✅ Recovery
- When asset goes missing, check transfer history
- Contact last known holder

---

## 📞 Support

For questions or issues with transfer tracking:
1. Check this documentation
2. Review activity logs in database
3. Contact system administrator
4. Report bugs via GitHub issues

---

## 🎓 Training Notes

**Key Points to Train Staff:**
1. Always record transfers when they occur
2. Get contact info of new holder
3. Add notes explaining transfer reason
4. Check transfer history before marking asset as lost
5. Use indirect return detection feature

**Common Mistakes to Avoid:**
- ❌ Forgetting to record transfers
- ❌ Not getting contact information
- ❌ Skipping transfer notes
- ❌ Recording transfer after return (record before!)

---

## Version History

**v1.0 - November 9, 2025**
- Initial implementation
- Basic transfer recording
- Transfer history view
- Automatic indirect return detection
- Full chain of custody tracking

---

## Credits

**Implemented by:** Claude (Anthropic)
**Database Schema:** Pre-existing (perfect design!)
**Frontend Framework:** Tailwind CSS + SweetAlert2
**Backend:** PHP 8.x + MariaDB
