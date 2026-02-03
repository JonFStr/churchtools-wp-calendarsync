# Pending Improvements for ChurchTools WP Calendar Sync

This file contains identified issues and improvements from code review that should be addressed in future updates.

## Completed (2026-02-04)
- [x] Error handling for external API calls - Added try-catch for AppointmentRequest, CombinedAppointmentRequest, FileRequest
- [x] File download error handling - Added error checking for file_get_contents calls

## Completed (2026-02-03)
- [x] SQL Injection vulnerabilities - Fixed with prepared statements
- [x] XSS vulnerabilities - Fixed with proper escaping
- [x] CSRF protection - Added nonce verification
- [x] API token exposure - Token no longer in HTML source
- [x] Capability checks - Added manage_options check
- [x] Connection testing error logging - Added error_log() calls to AJAX callbacks with [ChurchTools Sync] prefix
- [x] UI error tooltips - Error indicators now show detailed messages on hover
- [x] PHP 8.2 return type fixes - Fixed nullable return type violations in migration functions
- [x] Options validation - Added check for false/non-array options before SyncConfig::fromOptions()

---

## High Priority

### 1. Input Validation
**File:** `includes/SyncConfig.php`

**Issues:**
- No URL format validation
- No range validation for import_past/import_future
- No validation that calendar IDs are numeric

**Recommendation:**
```php
$url = esc_url_raw($url);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return null;
}

$import_past = max(-365, min(365, (int)$import_past));
```

---

## Medium Priority

### 2. Refactor Large Function
**File:** `churchtools-dosync.php`
**Lines:** 124-514 (`processCalendarEntry` function)

**Issue:** Function has ~390 lines handling multiple responsibilities

**Recommendation:** Break into smaller functions:
- `processEventContent()`
- `processEventLocation()`
- `processEventAttachments()`
- `processEventCategories()`

### 3. Performance: Location Lookup
**File:** `churchtools-dosync.php`
**Lines:** 535-550

**Issue:** Loads ALL locations and loops through them (O(n) for every event)

**Recommendation:** Use database query with proper parameters or implement caching

### 4. Performance: N+1 Query in Category Sync
**File:** `churchtools-dosync.php`
**Lines:** 636-659

**Issue:** Multiple `get_terms()` calls per calendar entry

**Recommendation:** Batch fetch categories at start of sync

### 5. Hardcoded German String
**File:** `churchtools-dosync.php`
**Line:** 808

**Issue:**
```php
$invalidRightsHeader = "Keine ausreichende Berechtigung";
```

**Recommendation:**
```php
$invalidRightsHeader = __("Insufficient permissions", "ctwpsync");
```

### 6. DateTime Validation
**File:** `churchtools-dosync.php`
**Lines:** 250-252, 305-306

**Issue:** `DateTime::createFromFormat()` returns false on invalid format but result not checked

**Recommendation:**
```php
$sDate = \DateTime::createFromFormat('Y-m-d', $ctCalEntry->getStartDate());
if (!$sDate) {
    logError("Invalid start date format: " . $ctCalEntry->getStartDate());
    return;
}
```

### 7. Session Destruction
**File:** `churchtools-dosync.php`
**Line:** 112

**Issue:** `session_destroy()` called in catch block - affects entire session

**Recommendation:** Remove or replace with more targeted cleanup

---

## Low Priority

### 8. Code Style Consistency
**File:** `churchtools-dosync.php`

**Issues:**
- Mixed spacing around operators
- Inconsistent variable naming (camelCase vs snake_case)
- Inconsistent brace placement

### 9. Logging Improvements
**File:** `churchtools-dosync.php`

**Issue:** Using `serialize()` in logs is hard to read

**Recommendation:** Use structured logging with proper formatting

### 10. Documentation
**Files:** Multiple

**Issue:** Some functions lack proper PHPDoc blocks

---

## Notes

- These improvements were identified during a security-focused code review
- Critical security issues have been addressed in commit `9b9bc25`
- Priority should be given to error handling improvements before the next release
