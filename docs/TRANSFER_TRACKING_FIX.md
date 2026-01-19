# Asset Transfer Tracking - Bug Fixes

**Date**: January 12, 2025
**Status**: ✅ Complete

## Issues Fixed

### 1. Missing Column Error: `asset_code`
**Error**: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'a.asset_code' in 'field list'`

**Root Cause**: The `assets` table doesn't have an `asset_code` column. Instead, it has `barcode` and `serial_number` columns.

**Solution**: Updated all SQL queries in [api/transfer_asset.php](../api/transfer_asset.php) to use:
```sql
COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code
```

**Files Modified**:
- `api/transfer_asset.php` (Lines 92, 109, 235, 292, 323)

---

### 2. Invalid Action Error
**Error**: `{"success":false,"message":"Invalid action"}`

**Root Cause**: The API wasn't parsing JSON input from POST requests. When data is sent as `Content-Type: application/json`, it's not automatically available in `$_POST`.

**Solution**: Added JSON parsing logic similar to `api/requests.php`:
```php
// Parse JSON input if Content-Type is application/json
$inputData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SERVER['CONTENT_TYPE']) &&
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true) ?? [];
    $_POST = array_merge($_POST, $inputData);
}
```

**Files Modified**:
- `api/transfer_asset.php` (Lines 22-34)
- Updated `recordTransfer()` function signature to accept `$inputData` parameter
- Updated `verifyBorrower()` function signature to accept `$inputData` parameter

---

### 3. Foreign Key Constraint Violation
**Error**: `SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails`

**Root Cause**: The `borrowing_chain` table required a non-null `borrowing_id`, but transfers for asset requests don't have a borrowing record yet.

**Solution**:
1. Created migration to modify database schema:
   - Made `borrowing_id` nullable
   - Added `request_id` column to support both borrowings and requests
   - Added foreign key constraints for both columns
   - Added check constraint to ensure either `borrowing_id` or `request_id` is provided

2. Updated INSERT query to include `request_id`:
```php
INSERT INTO borrowing_chain (
    borrowing_id,
    request_id,      // NEW
    asset_id,
    from_person,
    to_person,
    ...
) VALUES (?, ?, ?, ?, ?, ...)
```

**Files Created**:
- `database/migrations/fix_borrowing_chain_for_requests.sql`

**Files Modified**:
- `api/transfer_asset.php` (Lines 145-169)

---

## Database Schema Changes

### Table: `borrowing_chain`

**Before**:
```sql
borrowing_id INT(11) NOT NULL
```

**After**:
```sql
borrowing_id INT(11) NULL COMMENT 'Original borrowing record (NULL if tracking a request)',
request_id INT(11) NULL COMMENT 'Asset request ID (if transfer is for a request)'
```

**Constraints**:
- Foreign key: `borrowing_id` → `asset_borrowings.id` (ON DELETE CASCADE)
- Foreign key: `request_id` → `asset_requests.id` (ON DELETE CASCADE)
- Check constraint: `(borrowing_id IS NOT NULL) OR (request_id IS NOT NULL)`

---

## Testing Checklist

- [x] Transfer recording works for asset requests
- [x] Transfer recording works for asset borrowings
- [x] Transfer history displays correctly
- [x] No SQL errors when inserting transfer records
- [x] Foreign key constraints are properly enforced
- [x] JSON API requests are parsed correctly

---

## Usage Notes

### Recording a Transfer for a Request
```javascript
fetch('../api/transfer_asset.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
        action: 'record_transfer',
        request_id: 123,        // For requests
        from_person: 'John Doe',
        to_person: 'Jane Smith',
        transfer_date: '2025-01-12 10:00:00',
        notes: 'Transfer notes'
    })
})
```

### Recording a Transfer for a Borrowing
```javascript
fetch('../api/transfer_asset.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
        action: 'record_transfer',
        borrowing_id: 456,      // For borrowings
        from_person: 'John Doe',
        to_person: 'Jane Smith',
        transfer_date: '2025-01-12 10:00:00',
        notes: 'Transfer notes'
    })
})
```

---

## Related Files

- **API Endpoint**: `api/transfer_asset.php`
- **Frontend**: `custodian/return_assets.php` (Lines 839-980)
- **Migration**: `database/migrations/fix_borrowing_chain_for_requests.sql`
- **Database Table**: `borrowing_chain`

---

## Impact

✅ **No Breaking Changes**: Existing functionality for borrowing transfers remains intact
✅ **Backward Compatible**: Old transfer records still work
✅ **Enhanced Tracking**: Now supports transfer tracking for both borrowings and requests
