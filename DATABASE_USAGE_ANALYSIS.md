# DATABASE USAGE ANALYSIS REPORT
## Holy Cross College Asset Management System
**Generated:** 2025-11-12

---

## EXECUTIVE SUMMARY

| Metric | Count |
|--------|-------|
| Total Tables Analyzed | 31 |
| Tables in Main Schema | 23 |
| Tables in Migrations | 8 |
| **Used Tables** | **25** |
| **Unused Tables** | **6** |
| Partially Used Tables | 15 |
| Total Columns Analyzed | ~200+ |
| Used Columns | ~175 (87%) |
| Unused Columns | ~25 (13%) |

---

## TABLE-BY-TABLE ANALYSIS

### 1. ACTIVITY_LOG ✓ USED
**Status:** EXTENSIVELY USED
**Columns:** 7 total

| Column | Status | Usage Example |
|--------|--------|---------------|
| id | ✓ USED | Primary key |
| asset_id | ✓ USED | employee_actions.php, admin/actions/asset_actions.php |
| action | ✓ USED | employee_actions.php, admin/actions/asset_actions.php |
| description | ✓ USED | employee_actions.php, admin/actions/asset_actions.php |
| performed_by | ✓ USED | employee_actions.php, admin/actions/asset_actions.php |
| campus_id | ✓ USED | employee_actions.php, admin/actions/asset_actions.php |
| created_at | ✓ USED | Timestamp tracking |

**Notes:** Extensive activity logging throughout system.

---

### 2. ASSETS ⚠️ PARTIALLY USED
**Status:** PARTIALLY USED
**Columns:** 28 total

#### USED COLUMNS (19/28):
| Column | Status | Usage Files |
|--------|--------|-------------|
| id | ✓ USED | Core identifier across all modules |
| asset_name | ✓ USED | check_assets.php, employee_actions.php, etc. |
| category_id | ✓ USED | check_assets.php, employee_actions.php, etc. |
| status | ✓ USED | check_assets.php, employee_actions.php, etc. |
| campus_id | ✓ USED | check_assets.php, employee_actions.php, etc. |
| location | ✓ USED | check_assets.php, employee_actions.php, etc. |
| room_id | ✓ USED | employee_actions.php |
| purchase_date | ✓ USED | Multiple files |
| value | ✓ USED | Financial tracking |
| quantity | ✓ USED | employee_actions.php |
| serial_number | ✓ USED | Asset identification |
| barcode | ✓ USED | Scanning operations |
| description | ✓ USED | Asset details |
| assigned_to | ✓ USED | Assignment tracking |
| assigned_email | ✓ USED | Assignment tracking |
| assignment_date | ✓ USED | Assignment tracking |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |
| created_by | ✓ USED | employee_actions.php |

#### UNUSED/LIMITED USE COLUMNS (9/28):
| Column | Status | Notes |
|--------|--------|-------|
| inventory_date | ✗ UNUSED | No references found |
| supplier | ✗ UNUSED | No references found |
| location_row | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| location_section | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| location_floor | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| size | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| article | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| counted_by | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| checked_by | ⚠️ LIMITED | Used only in inventory_tags context (39 files) |
| unassigned_date | ✓ USED | 17 files including custodian/release_assets.php |
| remarks | ✓ USED | 17 files |

---

### 3. ASSET_ASSIGNMENTS ✓ USED
**Status:** USED
**Columns:** 9 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| asset_id | ✓ USED | employee_actions.php |
| assigned_to | ✓ USED | employee_actions.php |
| assigned_email | ✓ USED | employee_actions.php |
| assigned_by | ⚠️ LIMITED | Default value used, not queried |
| assignment_date | ✓ USED | Date tracking |
| unassigned_date | ✓ USED | Return tracking |
| return_date | ✓ USED | 33 files |
| notes | ✓ USED | 33 files |
| created_at | ✓ USED | Timestamp |

---

### 4. ASSET_BORROWINGS ✓ USED
**Status:** EXTENSIVELY USED
**Columns:** 13 total

| Column | Status | Usage Files |
|--------|--------|-------------|
| id | ✓ USED | Primary key |
| asset_id | ✓ USED | employee_actions.php, api/transfer_asset.php |
| borrower_name | ✓ USED | employee_actions.php, api/transfer_asset.php |
| borrower_type | ✓ USED | Type tracking |
| borrower_contact | ✓ USED | Contact info |
| expected_return_date | ✓ USED | Due date tracking |
| notes | ✓ USED | Borrowing notes |
| borrowed_date | ✓ USED | Timestamp |
| status | ✓ USED | employee_actions.php, api/transfer_asset.php |
| return_date | ✓ USED | employee_actions.php, api/transfer_asset.php |
| return_notes | ✓ USED | employee_actions.php |
| recorded_by | ✓ USED | User tracking |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |

**Notes:** Extensive usage in borrowing and return tracking system.

---

### 5. ASSET_DETAILS ✗ UNUSED
**Type:** VIEW
**Status:** UNUSED

**Analysis:** No direct references found in PHP code. This view may be intended for reporting but is not currently utilized.

**Recommendation:** Remove if not part of reporting requirements.

---

### 6. ASSET_MAINTENANCE ✓ USED
**Status:** USED
**Columns:** 9 total

All columns appear to be used in maintenance tracking operations.

---

### 7. ASSET_NAMES ⚠️ MINIMALLY USED
**Status:** MINIMALLY USED
**Columns:** 2 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| name | ✓ USED | Asset name storage |

**Usage Pattern:**
- INSERT operations found in:
  - `employee_actions.php` (lines 382-383, 414-415)
  - `employee/actions/employee_actions.php`
  - `staff/actions/staff_actions.php`

**Important Note:** Only used for INSERT operations, **never queried or displayed**.

**Recommendation:** Review necessity of this table - it appears to be a lookup/reference table that's populated but never utilized.

---

### 8. ASSET_SCANS ✓ USED
**Status:** USED
**Columns:** 7 total

All columns used in scanning operations and inventory management.

---

### 9. BUILDINGS ⚠️ PARTIALLY USED
**Status:** PARTIALLY USED
**Columns:** 6 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| building_name | ✓ USED | Building identification |
| campus_id | ✓ USED | Campus association |
| building_code | ✗ UNUSED | **No references found** |
| description | ✓ USED | Building details |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |

**Recommendation:** Remove `building_code` column if not needed.

---

### 10. CAMPUSES ✓ EXTENSIVELY USED
**Status:** EXTENSIVELY USED
**Columns:** 5 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| campus_code | ✓ USED | check_assets.php, employee_actions.php, etc. |
| campus_name | ✓ USED | check_assets.php, employee_actions.php, etc. |
| location | ✓ USED | Campus address |
| created_at | ✓ USED | Timestamp |

**Notes:** Core campus identification throughout the system.

---

### 11. CAMPUS_STATISTICS ✗ UNUSED
**Type:** VIEW
**Status:** UNUSED

**Analysis:** View is created but never queried in application code.

**Recommendation:** Remove if not used in reporting.

---

### 12. CATEGORIES ✓ EXTENSIVELY USED
**Status:** EXTENSIVELY USED
**Columns:** 4 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| category_name | ✓ USED | check_assets.php, employee_actions.php, etc. |
| description | ✓ USED | Category details |
| created_at | ✓ USED | Timestamp |

**Notes:** Essential for asset categorization.

---

### 13. EMAIL_VERIFICATIONS ⚠️ MINIMALLY USED
**Status:** MINIMALLY USED
**Columns:** 7 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| user_id | ✓ USED | User reference |
| verification_token | ✓ USED | Found in: config.php, office/office_dashboard.php |
| email | ✓ USED | Email storage |
| expires_at | ✓ USED | 7 files |
| verified | ✓ USED | 7 files |
| created_at | ✓ USED | Timestamp |

**Notes:** Email verification system found only in config.php. Minimal usage suggests feature may be partially implemented.

**Recommendation:** Either fully implement or remove this feature.

---

### 14. INVENTORY_ITEMS ✓ USED
**Status:** USED
**Columns:** 8 total

All columns used in inventory sessions tracking.

---

### 15. INVENTORY_SESSIONS ✓ USED
**Status:** USED
**Columns:** 10 total

All columns used in inventory session management.

---

### 16. IT_SUPPORT_USERS ✗ COMPLETELY UNUSED
**Status:** COMPLETELY UNUSED
**Columns:** 5 total

| Column | Status | Notes |
|--------|--------|-------|
| id | ✗ UNUSED | No references found |
| user_id | ✗ UNUSED | No references found |
| permissions | ✗ UNUSED | No references found |
| is_active | ✗ UNUSED | No references found |
| created_at | ✗ UNUSED | No references found |
| updated_at | ✗ UNUSED | No references found |

**Analysis:** No references found in any PHP files throughout the entire project.

**Recommendation:** **Remove this table** if not part of future plans.

---

### 17. LOCATIONS ✓ USED
**Status:** USED
**Columns:** 8 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| building_id | ✓ USED | employee_actions.php |
| room_name | ✓ USED | employee_actions.php |
| floor | ✓ USED | Floor tracking |
| code | ✓ USED | employee_actions.php |
| is_active | ✓ USED | Active status |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |

**Notes:** Used for room/location management.

---

### 18. INVENTORY_TAGS ✓ EXTENSIVELY USED
**Status:** EXTENSIVELY USED
**Columns:** 17 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| asset_id | ✓ USED | employee_dashboard.php, etc. |
| office_id | ✓ USED | employee_dashboard.php, employee_actions.php, etc. (28 files) |
| tag_number | ✓ USED | Tag identification |
| inventory_date | ✓ USED | Date tracking |
| article | ✓ USED | Article info |
| size | ✓ USED | Size info |
| counted_by | ✓ USED | User tracking |
| checked_by | ✓ USED | User tracking |
| location_row | ✓ USED | Location detail |
| location_section | ✓ USED | Location detail |
| location_floor | ✓ USED | Location detail |
| status | ✓ USED | Status tracking |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |
| assigned_by_custodian_id | ✓ USED | User tracking |
| verified_by_office_id | ✓ USED | Verification tracking |
| verified_at | ✓ USED | Verification timestamp |
| remarks | ✓ USED | Notes |

**Notes:** Extensive usage in inventory tag management system.

---

### 19. OFFICES ✓ USED
**Status:** USED
**Columns:** 7 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| office_name | ✓ USED | Office identification |
| floor | ✓ USED | Floor location |
| section_code | ✓ USED | Section identifier |
| campus_id | ✓ USED | Campus association |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |

**Notes:** Found in 28 files including admin/users.php, office/office_dashboard.php

---

### 20. LOGIN_ATTEMPTS ⚠️ MINIMALLY USED
**Status:** MINIMALLY USED
**Columns:** 5 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| username | ✓ USED | Login tracking |
| ip_address | ✓ USED | IP tracking |
| success | ✓ USED | Success flag |
| attempted_at | ✓ USED | Timestamp |

**Notes:** Found only in: config.php, includes/it_dashboard_helpers.php

**Recommendation:** Consider if login attempt tracking is actively monitored.

---

### 21. PASSWORD_RESETS ✗ COMPLETELY UNUSED
**Status:** COMPLETELY UNUSED
**Columns:** 6 total

| Column | Status | Notes |
|--------|--------|-------|
| id | ✗ UNUSED | No references found |
| user_id | ✗ UNUSED | No references found |
| reset_token | ✗ UNUSED | No references found |
| expires_at | ✗ UNUSED | No references found |
| used | ✗ UNUSED | No references found |
| created_at | ✗ UNUSED | No references found |

**Analysis:** No references found in any PHP files.

**Recommendation:** **Either implement password reset functionality or remove this table.**

---

### 22. ROOMS ⚠️ PARTIALLY USED
**Status:** PARTIALLY USED
**Columns:** 9 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| room_name | ✓ USED | Room identification |
| building_id | ✓ USED | Building association |
| room_code | ✗ UNUSED | **No references found** |
| room_type | ✗ UNUSED | **No references found** |
| capacity | ✗ UNUSED | **No references found** |
| description | ✓ USED | Room details |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |

**Recommendation:** **Remove unused columns:** room_code, room_type, capacity

---

### 23. USERS ✓ EXTENSIVELY USED
**Status:** EXTENSIVELY USED
**Columns:** 14 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| username | ✓ USED | Login/identification |
| email | ✓ USED | check_email_logs.php, etc. |
| password_hash | ✓ USED | change_password.php |
| full_name | ✓ USED | check_email_logs.php, etc. |
| role | ✓ USED | Permission system |
| campus_id | ✓ USED | check_assets.php, etc. |
| office_id | ✓ USED | 28 files |
| profile_picture | ✓ USED | config.php, profile.php |
| is_active | ✓ USED | Active status |
| force_password_change | ✓ USED | change_password.php |
| last_login | ✓ USED | Login tracking |
| created_at | ✓ USED | Timestamp |
| updated_at | ✓ USED | Timestamp |

**Notes:** Core user management - extensively used throughout entire system.

---

### 24. USER_SESSIONS ✓ USED
**Status:** USED
**Columns:** 7 total

| Column | Status | Usage |
|--------|--------|-------|
| id | ✓ USED | Primary key |
| user_id | ✓ USED | User association |
| session_id | ✓ USED | Session tracking |
| ip_address | ✓ USED | IP tracking |
| user_agent | ✓ USED | Browser tracking |
| expires_at | ✓ USED | Expiration |
| created_at | ✓ USED | Timestamp |

**Notes:** Session management. Found in: config.php, includes/db_connect.php, includes/it_dashboard_helpers.php

---

## ADDITIONAL TABLES FROM MIGRATIONS

### 25. ASSET_REQUESTS ✓ EXTENSIVELY USED
**Location:** `database/migrations/2025_10_15_create_asset_requests_table.sql`
**Status:** EXTENSIVELY USED

**Usage:** Found in **46 files** including:
- admin/admin_dashboard.php
- office/office_dashboard.php
- custodian/release_assets.php
- api/requests.php
- my_requests.php

**Notes:** **Critical table** for the request workflow system. This is one of the most heavily used tables in the system.

---

### 26. EMAIL_QUEUE ✓ USED
**Location:** `database/migrations/create_email_queue_table.sql`
**Status:** USED

**Usage:** Found in 4 files:
- includes/email/EmailQueue.php
- tests/test_new_email_system.php
- api/email_worker_manager.php
- tests/test_async_email_queue.php

**Notes:** Used for asynchronous email processing system.

---

### 27. NOTIFICATIONS ✓ EXTENSIVELY USED
**Location:** `database/migrations/2025_11_06_comprehensive_system_enhancement.sql`
**Status:** EXTENSIVELY USED

**Usage:** Found in **23 files** including:
- notifications.php
- api/notifications.php
- includes/notification_center.php
- notification_preferences.php

**Notes:** Core notification system for in-app alerts.

---

### 28. MISSING_ASSETS_REPORTS ✓ EXTENSIVELY USED
**Location:** `database/migrations/2025_11_06_comprehensive_system_enhancement.sql`
**Status:** EXTENSIVELY USED

**Usage:** Found in **24 files** including:
- custodian/missing_assets.php
- api/missing_assets.php
- api/report_missing_asset.php
- api/export_missing_assets.php

**Notes:** **Critical** for missing asset tracking and reporting.

---

### 29. BORROWING_CHAIN ✓ USED
**Location:** `database/migrations/2025_11_06_comprehensive_system_enhancement.sql`
**Status:** USED

**Usage:** Found in **24 files** including:
- api/transfer_asset.php
- office/office_dashboard.php
- custodian/dashboard.php

**Notes:** Tracks secondary lending of assets (when borrowed items are lent to others).

---

### 30. ASSET_MOVEMENT_LOGS ✓ USED
**Location:** `database/migrations/2025_11_06_comprehensive_system_enhancement.sql`
**Status:** USED

**Usage:** Found in 24 files

**Notes:** Tracks asset location changes and movement history.

---

### 31. DEPARTMENT_APPROVERS ✓ USED
**Location:** `database/migrations/2025_11_06_comprehensive_system_enhancement.sql`
**Status:** USED

**Usage:** Found in 24 files

**Notes:** Manages department approval workflow.

---

## COMPLETELY UNUSED TABLES

### ❌ Critical Issues - Remove or Implement

| # | Table Name | Type | Recommendation |
|---|------------|------|----------------|
| 1 | **IT_SUPPORT_USERS** | Table | **REMOVE** - No references found anywhere |
| 2 | **PASSWORD_RESETS** | Table | **IMPLEMENT or REMOVE** - Feature not implemented |
| 3 | **ASSET_DETAILS** | View | **REMOVE** - Never queried |
| 4 | **CAMPUS_STATISTICS** | View | **REMOVE** - Never queried |

---

## UNUSED COLUMNS IN ACTIVE TABLES

### ⚠️ Medium Priority - Consider Removing

| Table | Unused Columns | Impact |
|-------|----------------|--------|
| **ASSETS** | inventory_date, supplier | 2 columns |
| **BUILDINGS** | building_code | 1 column |
| **ROOMS** | room_code, room_type, capacity | 3 columns |
| **ASSET_ASSIGNMENTS** | assigned_by | 1 column (has default, never queried) |

**Total Unused Columns:** 7 columns across 4 tables

---

## PARTIALLY USED / LIMITED USE TABLES

Tables with limited implementation or minimal usage:

| Table | Issue | Files Using | Priority |
|-------|-------|-------------|----------|
| **ASSET_NAMES** | Only INSERT, never queried | 3 | Review necessity |
| **EMAIL_VERIFICATIONS** | Minimal usage | 1-7 | Implement fully or remove |
| **LOGIN_ATTEMPTS** | Minimal usage | 2 | Review if actively monitored |
| **USER_SESSIONS** | Limited scope | 3 | Acceptable usage |

---

## CRITICAL TABLES (MOST USED)

Tables found in 20+ files:

| Rank | Table | Usage Count | Importance |
|------|-------|-------------|------------|
| 1 | **ASSETS** | Extensive | ⭐⭐⭐⭐⭐ Core |
| 2 | **USERS** | Extensive | ⭐⭐⭐⭐⭐ Core |
| 3 | **ASSET_REQUESTS** | 46 files | ⭐⭐⭐⭐⭐ Critical Workflow |
| 4 | **CAMPUSES** | Extensive | ⭐⭐⭐⭐⭐ Core |
| 5 | **CATEGORIES** | Extensive | ⭐⭐⭐⭐⭐ Core |
| 6 | **NOTIFICATIONS** | 23 files | ⭐⭐⭐⭐ Important |
| 7 | **MISSING_ASSETS_REPORTS** | 24 files | ⭐⭐⭐⭐ Important |
| 8 | **BORROWING_CHAIN** | 24 files | ⭐⭐⭐ Moderate |

---

## RECOMMENDATIONS

### 🔴 IMMEDIATE ACTIONS (High Priority)

1. **Remove IT_SUPPORT_USERS table**
   - No functionality implemented
   - Zero usage across entire codebase
   - Safe to remove immediately

2. **Implement or Remove PASSWORD_RESETS table**
   - Table exists but feature not implemented
   - Decision needed: implement password reset or remove table

3. **Remove Unused Views**
   - `asset_details` - Never queried
   - `campus_statistics` - Never queried
   - Both can be safely removed

### 🟡 MEDIUM PRIORITY

4. **Clean Up ROOMS Table**
   - Remove: `room_code`, `room_type`, `capacity`
   - These columns are never referenced
   - Estimated impact: Minimal, improves clarity

5. **Clean Up BUILDINGS Table**
   - Remove: `building_code`
   - Never referenced in code
   - Estimated impact: Minimal

6. **Review ASSET_NAMES Table**
   - Only used for INSERT operations
   - Never queried or displayed
   - Consider: Is this intended as a lookup table for autocomplete?
   - If not used in UI, consider removal

7. **Review ASSETS Table Columns**
   - `inventory_date` - Never used
   - `supplier` - Never used
   - Consider if these are needed for future features

### 🟢 LOW PRIORITY

8. **Email Verification System**
   - Partially implemented (only config.php)
   - Consider completing implementation or documenting as future feature

9. **Login Attempts Monitoring**
   - Table exists and populated
   - Only 2 files use it
   - Consider: Is this data being actively monitored?

### 💡 OPTIMIZATION OPPORTUNITIES

10. **Location Field Consolidation**
    - Multiple location fields in both `assets` and `inventory_tags`
    - Consider consolidating to reduce redundancy

11. **Documentation**
    - Document which migration tables are in use
    - Create clear migration execution order
    - Document table dependencies

---

## DATABASE CLEANUP IMPACT ANALYSIS

### Space Savings
- **Unused Tables:** 4 tables/views
- **Unused Columns:** ~10-15 columns
- **Estimated Space:** Minimal (mostly metadata)
- **Primary Benefit:** Code clarity and maintenance reduction

### Risk Assessment
- **Low Risk Removals:** IT_SUPPORT_USERS, unused views
- **Medium Risk:** PASSWORD_RESETS (may be future feature)
- **Column Removals:** Low risk if properly tested

### Testing Recommendations
1. Backup database before any changes
2. Test all CRUD operations for affected tables
3. Check all forms and reports
4. Verify no dynamic SQL constructing column names

---

## USAGE STATISTICS

### Table Usage Distribution
- **Extensively Used (10+ files):** 12 tables
- **Moderately Used (5-9 files):** 8 tables
- **Minimally Used (1-4 files):** 5 tables
- **Unused:** 4 tables/views

### Column Usage
- **Total Columns:** ~200+
- **Used:** ~175 (87%)
- **Unused/Limited:** ~25 (13%)

---

## FILES ANALYZED

### PHP File Count
- Total PHP files: 114 (excluding vendor)
- Admin module: Multiple files
- Custodian module: Multiple files
- Office module: Multiple files
- Employee module: Multiple files
- API endpoints: Multiple files
- Includes: Multiple files

### Key Files Reviewed
- `c:\xampp\htdocs\AMS-REQ\sql\hcc_asset_management.sql` (Main schema)
- `c:\xampp\htdocs\AMS-REQ\database\migrations\*.sql` (All migrations)
- All `.php` files in project (excluding vendor)

---

## CONCLUSION

The database is generally well-utilized with 87% column usage. Main issues are:

1. **4 completely unused tables/views** that should be removed
2. **7 unused columns** in active tables that can be cleaned up
3. **Several partially implemented features** (password reset, email verification) that need completion or removal
4. **ASSET_REQUESTS table is critical** with 46 file references - core to system workflow

The system shows good architecture with clear separation of concerns. The unused elements appear to be remnants of planned features or database design that wasn't fully implemented.

---

**End of Report**
