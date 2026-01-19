# API Requests.php Refactoring Plan

## Executive Summary

The `api/requests.php` file (1,659 lines) requires comprehensive refactoring to improve:
- **Maintainability**: Split into focused, testable components
- **Reusability**: Extract common patterns and logic
- **Testability**: Separate business logic from routing
- **Security**: Centralize validation and authorization
- **Performance**: Optimize repeated queries

**Estimated Effort**: 3-5 days
**Risk Level**: Medium (High usage, but well-tested endpoints)
**Backward Compatibility**: 100% maintained

---

## Current Problems Analysis

### 1. Code Structure Issues
- **Single File Monolith**: 1,659 lines, 20+ actions in one switch statement
- **No Separation of Concerns**: API routing, business logic, database queries, notifications all mixed
- **Deep Nesting**: Up to 5 levels deep in some switch cases
- **Duplicated Code**: Similar patterns repeated across 10+ actions

### 2. Database Issues
- **Repeated Queries**: Same JOIN patterns used 8+ times
- **No Query Builder**: Raw SQL strings scattered throughout
- **Inconsistent Transactions**: Some actions use transactions, others don't
- **N+1 Potential**: Notifications sent in loops without batching

### 3. Business Logic Issues
- **Approval Logic Scattered**: Workflow rules spread across 4+ actions
- **Hardcoded Statuses**: 'pending', 'approved', etc. embedded everywhere
- **Complex Permission Checks**: Role validation duplicated in each action
- **Notification Logic Duplicated**: Similar patterns for email/in-app notifications

### 4. Maintenance Issues
- **Hard to Test**: No unit tests possible without major changes
- **Hard to Debug**: Long procedural code with mixed concerns
- **Hard to Extend**: Adding new approval flows requires editing multiple places
- **Poor Error Messages**: Generic exceptions with limited context

---

## Proposed Architecture

### New Directory Structure
```
includes/
├── services/
│   ├── RequestService.php          # Core business logic
│   ├── ApprovalService.php         # Approval workflow
│   ├── NotificationService.php     # Consolidated notifications
│   ├── InventoryTagService.php     # Tag generation
│   └── AssetReleaseService.php     # Asset release/return
├── repositories/
│   ├── RequestRepository.php       # Database queries
│   ├── AssetRepository.php         # Asset queries
│   └── UserRepository.php          # User queries
├── validators/
│   ├── RequestValidator.php        # Input validation
│   └── PermissionValidator.php     # Authorization
├── dto/
│   ├── CreateRequestDTO.php        # Data transfer objects
│   ├── ApprovalDTO.php
│   └── RequestResponseDTO.php
└── constants/
    ├── RequestStatus.php           # Status constants
    ├── ApprovalFlow.php            # Workflow constants
    └── NotificationType.php        # Notification types
```

### New API Structure
```
api/
├── requests.php                    # Thin routing layer (< 200 lines)
├── v2/
│   └── requests.php                # Optional: New versioned API
```

---

## Refactoring Strategy

### Phase 1: Foundation (Day 1)
**Goal**: Create base infrastructure without breaking existing code

#### 1.1 Create Constants Classes
Extract hardcoded values to constants:

**File**: `includes/constants/RequestStatus.php`
```php
class RequestStatus {
    const PENDING = 'pending';
    const OFFICE_REVIEW = 'office_review';
    const CUSTODIAN_REVIEW = 'custodian_review';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const RELEASED = 'released';
    const RETURNED = 'returned';

    public static function getAll(): array;
    public static function isValid(string $status): bool;
}
```

**File**: `includes/constants/ApprovalFlow.php`
```php
class ApprovalFlow {
    const OFFICE_FLOW = 'office';      // Employee → Office Head
    const CUSTODIAN_FLOW = 'custodian'; // Employee → Custodian → Admin

    public static function getNextStatus(string $flow, string $currentStatus): string;
    public static function getRequiredRole(string $flow, string $status): string;
}
```

**Impact**: Zero breaking changes, improves readability
**Time**: 2 hours

#### 1.2 Create Repository Layer
Extract database queries to repository pattern:

**File**: `includes/repositories/RequestRepository.php`
```php
class RequestRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Core queries
    public function findById(int $requestId): ?array;
    public function findPendingByRole(string $role, int $campusId, ?int $officeId = null): array;
    public function create(array $data): int;
    public function updateStatus(int $requestId, string $status, array $metadata = []): bool;

    // Specialized queries
    public function getApprovalHistory(int $userId, string $role, int $campusId): array;
    public function getOfficeRequestsForRelease(int $officeId, int $campusId): array;
    public function getOfficeRequestsForReturn(int $officeId, int $campusId): array;

    // Private helper methods
    private function buildRequestQuery(): string; // Reusable JOIN pattern
    private function mapRowToRequest(array $row): array;
}
```

**Benefits**:
- Eliminates 8+ repeated query patterns
- Centralized query optimization
- Easier to add caching later
- Testable without database

**Time**: 4 hours

#### 1.3 Create DTO (Data Transfer Objects)
Standardize data structures:

**File**: `includes/dto/CreateRequestDTO.php`
```php
class CreateRequestDTO {
    public int $assetId;
    public int $quantity;
    public string $purpose;
    public string $expectedReturnDate;
    public string $requestSource; // 'office' or 'custodian'
    public ?int $targetOfficeId;

    public static function fromArray(array $data): self;
    public function validate(): array; // Returns validation errors
}
```

**Benefits**:
- Type safety
- Self-documenting
- Easier to validate
- Can add serialization/deserialization

**Time**: 3 hours

---

### Phase 2: Extract Services (Day 2)
**Goal**: Move business logic out of API file

#### 2.1 Create RequestService
Core request management logic:

**File**: `includes/services/RequestService.php`
```php
class RequestService {
    private RequestRepository $repository;
    private NotificationService $notificationService;
    private AssetRepository $assetRepository;

    public function __construct(
        RequestRepository $repository,
        NotificationService $notificationService,
        AssetRepository $assetRepository
    ) {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
        $this->assetRepository = $assetRepository;
    }

    // Main operations
    public function createRequest(CreateRequestDTO $dto, int $userId, int $campusId): array;
    public function getPendingRequests(string $role, int $campusId, ?int $officeId = null): array;
    public function getRequestDetails(int $requestId): array;

    // Private helpers
    private function validateAssetAvailability(int $assetId, int $quantity, string $source, ?int $officeId): void;
    private function determineInitialStatus(string $requestSource): string;
    private function notifyApprovers(int $requestId, string $requestSource, array $request, array $user): void;
}
```

**Extracted Actions**: `create_request`, `get_pending_requests`, `get_request`
**Lines Reduced**: ~400 lines from requests.php
**Time**: 5 hours

#### 2.2 Create ApprovalService
Approval workflow logic:

**File**: `includes/services/ApprovalService.php`
```php
class ApprovalService {
    private RequestRepository $repository;
    private NotificationService $notificationService;
    private PermissionValidator $permissionValidator;

    public function approveByCustodian(int $requestId, int $userId, string $comments): array;
    public function approveByOffice(int $requestId, int $userId, string $comments): array;
    public function approveByAdmin(int $requestId, int $userId, string $comments): array;
    public function rejectRequest(int $requestId, int $userId, string $reason, string $userRole): array;

    // Private helpers
    private function validateApprovalPermission(array $request, string $role, int $userId): void;
    private function getNextApprover(string $flow, string $currentStatus, int $campusId): ?array;
    private function notifyNextApprover(int $requestId, array $approver, array $request): void;
    private function notifyRequester(int $requestId, string $status, array $request): void;
}
```

**Extracted Actions**: `approve_as_custodian`, `approve_as_office`, `approve_as_admin`, `reject_request`
**Lines Reduced**: ~600 lines from requests.php
**Time**: 6 hours

#### 2.3 Create AssetReleaseService
Asset release and return logic:

**File**: `includes/services/AssetReleaseService.php`
```php
class AssetReleaseService {
    private RequestRepository $requestRepository;
    private AssetRepository $assetRepository;
    private NotificationService $notificationService;
    private PDO $pdo;

    public function releaseAsset(int $requestId, int $userId): array;
    public function returnAsset(int $requestId, int $userId, ReturnAssetDTO $dto): array;

    // Private helpers
    private function createBorrowingRecord(array $request, string $recordedBy): int;
    private function updateAssetQuantity(int $assetId, int $quantity, string $operation): void;
    private function calculateOverdueDays(string $expectedDate, string $actualDate): int;
    private function handleLateReturn(int $requestId, int $userId, int $daysOverdue, string $remarks): void;
}
```

**Extracted Actions**: `release_asset`, `return_asset`
**Lines Reduced**: ~200 lines from requests.php
**Time**: 4 hours

#### 2.4 Create InventoryTagService
Tag generation logic:

**File**: `includes/services/InventoryTagService.php`
```php
class InventoryTagService {
    private PDO $pdo;
    private NotificationService $notificationService;

    public function generateRandomTagNumber(?int $requestId = null): string;
    public function generateTagForOfficeRequest(int $requestId, string $tagNumber, string $remarks, int $userId): array;
    public function autoGenerateTagAndTransfer(int $requestId, int $userId): array;

    // Private helpers
    private function extractOfficePrefix(string $officeName): string;
    private function generateUniqueTag(string $prefix): string;
    private function createInventoryTag(array $data): int;
    private function transferAssetToOffice(int $assetId, int $officeId, int $quantity): void;
}
```

**Extracted Actions**: `generate_random_tag_number`, `generate_tag_for_office_request`, `auto_generate_tag_and_transfer`
**Lines Reduced**: ~300 lines from requests.php
**Time**: 4 hours

---

### Phase 3: Validators & Middleware (Day 3)
**Goal**: Centralize validation and authorization

#### 3.1 Create Permission Validator
**File**: `includes/validators/PermissionValidator.php`
```php
class PermissionValidator {
    public function canApproveAsCustodian(array $user, array $request): bool;
    public function canApproveAsOffice(array $user, array $request): bool;
    public function canApproveAsAdmin(array $user, array $request): bool;
    public function canRejectRequest(array $user, array $request): bool;
    public function canReleaseAsset(array $user, array $request): bool;

    private function hasRole(array $user, string|array $roles): bool;
    private function isRequestInStatus(array $request, string|array $statuses): bool;
}
```

**Benefits**:
- Eliminates duplicated permission checks
- Centralized authorization logic
- Easier to audit security

**Time**: 3 hours

#### 3.2 Create Request Validator
**File**: `includes/validators/RequestValidator.php`
```php
class RequestValidator {
    public function validateCreateRequest(array $data): array; // Returns errors
    public function validateApproval(array $data): array;
    public function validateRejection(array $data): array;
    public function validateReturn(array $data): array;

    private function validateDate(string $date, string $fieldName): ?string;
    private function validateQuantity(int $quantity): ?string;
    private function validatePurpose(string $purpose): ?string;
}
```

**Time**: 2 hours

#### 3.3 Create NotificationService
Consolidate all notification logic:

**File**: `includes/services/NotificationService.php`
```php
class NotificationService {
    private PDO $pdo;
    private EmailQueue $emailQueue;

    // Request notifications
    public function notifyRequestCreated(int $requestId, array $approvers, array $request, array $requester): void;
    public function notifyRequestApproved(int $requestId, array $requester, array $approver, string $stage): void;
    public function notifyRequestRejected(int $requestId, array $requester, string $reason): void;
    public function notifyAssetReleased(int $requestId, array $requester, array $request): void;
    public function notifyAssetReturned(int $requestId, array $requester, bool $isLate, array $details): void;

    // Multi-channel notification
    private function sendInAppNotification(int $userId, string $type, string $title, string $message, array $metadata): void;
    private function queueEmailNotification(string $email, string $name, string $subject, string $body, string $priority): void;
}
```

**Benefits**:
- Single source of truth for notifications
- Consistent formatting
- Easier to add new channels (SMS, push, etc.)
- Can batch notifications

**Time**: 4 hours

---

### Phase 4: Refactor API Endpoint (Day 4)
**Goal**: Slim down requests.php to a thin routing layer

#### 4.1 New requests.php Structure
**File**: `api/requests.php`
```php
<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/services/RequestService.php';
require_once dirname(__DIR__) . '/includes/services/ApprovalService.php';
require_once dirname(__DIR__) . '/includes/services/AssetReleaseService.php';
require_once dirname(__DIR__) . '/includes/services/InventoryTagService.php';

requireLogin();
header('Content-Type: application/json');

// Initialize services
$requestRepository = new RequestRepository($pdo);
$assetRepository = new AssetRepository($pdo);
$notificationService = new NotificationService($pdo, new EmailQueue($pdo));
$permissionValidator = new PermissionValidator();

$requestService = new RequestService($requestRepository, $notificationService, $assetRepository);
$approvalService = new ApprovalService($requestRepository, $notificationService, $permissionValidator);
$releaseService = new AssetReleaseService($requestRepository, $assetRepository, $notificationService, $pdo);
$tagService = new InventoryTagService($pdo, $notificationService);

// Parse input
$input = parseRequestInput();
$action = $input['action'] ?? '';
$user = getUserInfo();

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFToken($input['csrf_token'] ?? '');
}

try {
    switch ($action) {
        case 'get_pending_requests':
            $result = $requestService->getPendingRequests(
                strtolower($user['role']),
                $user['campus_id'],
                $user['office_id'] ?? null
            );
            respondSuccess($result);
            break;

        case 'get_request':
            $result = $requestService->getRequestDetails((int)($input['request_id'] ?? 0));
            respondSuccess(['request' => $result]);
            break;

        case 'create_request':
            $dto = CreateRequestDTO::fromArray($input);
            $result = $requestService->createRequest($dto, $user['id'], $user['campus_id']);
            respondSuccess($result);
            break;

        case 'approve_as_custodian':
            $result = $approvalService->approveByCustodian(
                (int)$input['request_id'],
                $user['id'],
                $input['comments'] ?? ''
            );
            respondSuccess($result);
            break;

        // ... other cases (much simpler now)

        default:
            throw new Exception('Invalid action');
    }
} catch (ValidationException $e) {
    respondError($e->getMessage(), 400, $e->getErrors());
} catch (PermissionException $e) {
    respondError($e->getMessage(), 403);
} catch (Exception $e) {
    respondError($e->getMessage(), 400);
}

// Helper functions
function parseRequestInput(): array { /* ... */ }
function respondSuccess(array $data): void { /* ... */ }
function respondError(string $message, int $code, array $errors = []): void { /* ... */ }
```

**New File Size**: ~200 lines (from 1,659)
**Reduction**: 88% smaller
**Time**: 6 hours

---

### Phase 5: Testing & Validation (Day 5)
**Goal**: Ensure nothing broke

#### 5.1 Create Unit Tests
**New Directory**: `tests/services/`

```php
// tests/services/RequestServiceTest.php
class RequestServiceTest extends TestCase {
    public function testCreateRequestWithOfficeFlow() { /* ... */ }
    public function testCreateRequestWithCustodianFlow() { /* ... */ }
    public function testCreateRequestInsufficientQuantity() { /* ... */ }
    // ... 20+ test cases
}
```

**Time**: 6 hours

#### 5.2 Integration Testing
- Test each endpoint with Postman/curl
- Verify all 20+ actions still work
- Check notification delivery
- Validate database transactions

**Time**: 4 hours

#### 5.3 Performance Testing
- Compare query performance before/after
- Check for N+1 queries
- Validate transaction rollbacks

**Time**: 2 hours

---

## Migration Checklist

### Pre-Migration
- [ ] Create feature branch: `refactor/requests-api`
- [ ] Backup database
- [ ] Document current API behavior
- [ ] Set up error monitoring

### During Migration
- [ ] Phase 1: Create constants and repositories (Day 1)
- [ ] Phase 2: Extract services (Day 2)
- [ ] Phase 3: Add validators (Day 3)
- [ ] Phase 4: Refactor API endpoint (Day 4)
- [ ] Phase 5: Test everything (Day 5)

### Post-Migration
- [ ] Code review with team
- [ ] QA testing on staging
- [ ] Performance comparison
- [ ] Deploy to production
- [ ] Monitor for issues (24-48 hours)
- [ ] Update documentation
- [ ] Mark old patterns as deprecated

---

## Risk Mitigation

### Risk 1: Breaking Existing Functionality
**Mitigation**:
- Keep old file as backup (`requests.php.backup`)
- Run comprehensive integration tests
- Staged rollout (staging → production)
- Feature flag for easy rollback

### Risk 2: Performance Regression
**Mitigation**:
- Benchmark queries before/after
- Profile service layer overhead
- Add caching if needed
- Monitor production metrics

### Risk 3: Team Adoption
**Mitigation**:
- Provide migration guide for other endpoints
- Document new patterns thoroughly
- Pair programming sessions
- Code review all changes

---

## Success Metrics

### Code Quality
- [ ] Lines of code reduced by 70%+
- [ ] Cyclomatic complexity < 10 per method
- [ ] Test coverage > 80%
- [ ] Zero duplication in approval logic

### Performance
- [ ] Query count reduced by 30%+
- [ ] Response time < 200ms (95th percentile)
- [ ] No N+1 queries
- [ ] Transaction overhead < 5ms

### Maintainability
- [ ] New feature takes < 1 hour (vs 3+ hours)
- [ ] Bug fix time reduced by 50%
- [ ] Onboarding time for new devs reduced
- [ ] Documentation coverage 100%

---

## Future Enhancements (Post-Refactoring)

### Short Term (1-2 weeks)
1. Add request caching (Redis/Memcached)
2. Implement event system for notifications
3. Add request analytics/tracking
4. Create admin audit log

### Medium Term (1-2 months)
1. API versioning (v2 endpoint)
2. GraphQL endpoint option
3. Webhook support for integrations
4. Real-time updates via WebSockets

### Long Term (3-6 months)
1. Microservices architecture
2. Event sourcing for audit trail
3. Machine learning for approval predictions
4. Mobile app API optimization

---

## Appendix

### A. Code Comparison Examples

#### Before (requests.php)
```php
case 'approve_as_custodian':
    if (!hasRole('custodian') && !hasRole('admin')) {
        throw new Exception('Only custodians can approve at this level');
    }

    $requestId = (int)($_POST['request_id'] ?? 0);
    $comments = $_POST['comments'] ?? '';

    if (!$requestId) {
        throw new Exception('Request ID is required');
    }

    $stmt = $pdo->prepare("
        SELECT ar.*, a.asset_name, u.full_name as requester_name, u.email as requester_email
        FROM asset_requests ar
        JOIN assets a ON ar.asset_id = a.id
        JOIN users u ON ar.requester_id = u.id
        WHERE ar.id = ? AND ar.campus_id = ?
    ");
    $stmt->execute([$requestId, $user['campus_id']]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('Request not found or access denied');
    }

    if ($request['status'] !== 'pending') {
        throw new Exception('Request is not in pending status');
    }

    // ... 50+ more lines ...
```

#### After (requests.php)
```php
case 'approve_as_custodian':
    $result = $approvalService->approveByCustodian(
        (int)$input['request_id'],
        $user['id'],
        $input['comments'] ?? ''
    );
    respondSuccess($result);
    break;
```

**Lines Reduced**: 80+ lines → 6 lines

### B. Estimated Effort Breakdown

| Phase | Task | Hours | Days |
|-------|------|-------|------|
| 1 | Constants | 2 | 0.25 |
| 1 | Repository | 4 | 0.5 |
| 1 | DTOs | 3 | 0.375 |
| 2 | RequestService | 5 | 0.625 |
| 2 | ApprovalService | 6 | 0.75 |
| 2 | ReleaseService | 4 | 0.5 |
| 2 | TagService | 4 | 0.5 |
| 3 | Validators | 5 | 0.625 |
| 3 | NotificationService | 4 | 0.5 |
| 4 | Refactor API | 6 | 0.75 |
| 5 | Unit Tests | 6 | 0.75 |
| 5 | Integration Tests | 4 | 0.5 |
| 5 | Performance Tests | 2 | 0.25 |
| **Total** | | **55** | **6.875** |

**Recommended Timeline**: 7 business days (with buffer)

### C. Dependencies
- PHP 7.4+
- PDO extension
- Existing EmailQueue class
- Existing notification functions
- PHPUnit for testing (optional)

### D. Related Files to Update
After refactoring `requests.php`, consider similar refactoring for:
1. `api/assets.php` (likely 800+ lines)
2. `api/inventory_tags.php`
3. `api/offices.php`
4. `custodian/actions/custodian_actions.php`
5. `admin/actions/asset_actions.php`

---

## Questions & Answers

**Q: Will this break existing code?**
A: No, the API endpoints remain exactly the same. Only internal implementation changes.

**Q: Can we do this incrementally?**
A: Yes, extract one service at a time while keeping both old and new code working.

**Q: What about performance?**
A: Service layer adds minimal overhead (~1-2ms). Query optimization will improve overall performance.

**Q: Do we need to update frontend code?**
A: No, API contracts remain unchanged. Frontend is unaffected.

**Q: Can we revert if something goes wrong?**
A: Yes, keep old file as backup and use feature flags for easy rollback.

---

**Document Version**: 1.0
**Created**: 2025-01-12
**Author**: Development Team
**Status**: Ready for Review
