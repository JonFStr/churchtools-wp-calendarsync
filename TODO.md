# Pending Improvements for ChurchTools WP Calendar Sync

This file contains identified issues and improvements from code review that should be addressed in future updates.

## Completed (2026-02-03)
- [x] SQL Injection vulnerabilities - Fixed with prepared statements
- [x] XSS vulnerabilities - Fixed with proper escaping
- [x] CSRF protection - Added nonce verification
- [x] API token exposure - Token no longer in HTML source
- [x] Capability checks - Added manage_options check

---

## High Priority

### 1. Error Handling for External API Calls
**File:** `churchtools-dosync.php`
**Lines:** 91-94, 255, 265

**Issue:** No try-catch blocks around ChurchTools API calls:
- `AppointmentRequest::forCalendars()`
- `CombinedAppointmentRequest::forAppointment()`
- `FileRequest::forEvent()`

**Recommendation:**
```php
try {
    $result = AppointmentRequest::forCalendars($calendars)->where(...)->get();
} catch (\CTApi\Exceptions\CTRequestException $e) {
    logError("Failed to fetch appointments: " . $e->getMessage());
    return;
}
```

### 2. File Download Error Handling
**File:** `churchtools-dosync.php`
**Lines:** 687, 713

**Issue:** `file_get_contents($fileURL)` can fail silently

**Recommendation:**
```php
$content = @file_get_contents($fileURL);
if ($content === false) {
    logError("Failed to download image from " . $fileURL);
    return null;
}
```

### 3. Input Validation
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

### 4. Refactor Large Function
**File:** `churchtools-dosync.php`
**Lines:** 124-514 (`processCalendarEntry` function)

**Issue:** Function has ~390 lines handling multiple responsibilities

**Recommendation:** Break into smaller functions:
- `processEventContent()`
- `processEventLocation()`
- `processEventAttachments()`
- `processEventCategories()`

### 5. Performance: Location Lookup
**File:** `churchtools-dosync.php`
**Lines:** 535-550

**Issue:** Loads ALL locations and loops through them (O(n) for every event)

**Recommendation:** Use database query with proper parameters or implement caching

### 6. Performance: N+1 Query in Category Sync
**File:** `churchtools-dosync.php`
**Lines:** 636-659

**Issue:** Multiple `get_terms()` calls per calendar entry

**Recommendation:** Batch fetch categories at start of sync

### 7. Hardcoded German String
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

### 8. DateTime Validation
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

### 9. Session Destruction
**File:** `churchtools-dosync.php`
**Line:** 112

**Issue:** `session_destroy()` called in catch block - affects entire session

**Recommendation:** Remove or replace with more targeted cleanup

---

## Low Priority

### 10. Code Style Consistency
**File:** `churchtools-dosync.php`

**Issues:**
- Mixed spacing around operators
- Inconsistent variable naming (camelCase vs snake_case)
- Inconsistent brace placement

### 11. Logging Improvements
**File:** `churchtools-dosync.php`

**Issue:** Using `serialize()` in logs is hard to read

**Recommendation:** Use structured logging with proper formatting

### 12. Documentation
**Files:** Multiple

**Issue:** Some functions lack proper PHPDoc blocks

---

## Notes

- These improvements were identified during a security-focused code review
- Critical security issues have been addressed in commit `9b9bc25`
- Priority should be given to error handling improvements before the next release
