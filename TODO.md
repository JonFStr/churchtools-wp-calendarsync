# Pending Improvements for ChurchTools WP Calendar Sync

This file contains identified issues and improvements from code review that should be addressed in future updates.

## Completed (2026-02-04)
- [x] AJAX with saved token - Fixed Validate Connection, Load Calendars, Load Resource Types buttons to work with saved API token
- [x] Logging improvements - Replaced serialize() with json_encode() and formatted output
- [x] DateTime validation - Added checks for invalid date formats from createFromFormat()
- [x] Session destruction removed - Removed problematic session_destroy() from catch block
- [x] Input validation - Added URL validation, range clamping for import days, calendar ID validation
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

---

## Low Priority

### 8. Code Style Consistency
**File:** `churchtools-dosync.php`

**Issues:**
- Mixed spacing around operators
- Inconsistent variable naming (camelCase vs snake_case)
- Inconsistent brace placement

### 9. Documentation
**Files:** Multiple

**Issue:** Some functions lack proper PHPDoc blocks

---

## Notes

- These improvements were identified during a security-focused code review
- Critical security issues have been addressed in commit `9b9bc25`
- Priority should be given to error handling improvements before the next release
