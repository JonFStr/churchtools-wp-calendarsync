# PHP 8.2 Upgrade Summary

This document outlines the changes made to upgrade the ChurchTools WP Calendar Sync plugin to require PHP 8.2.

## Major Changes

### 1. Plugin Requirements
- Updated minimum PHP version from 8.0 to 8.2 in plugin header
- Location: `churchtools-wpcalendarsync.php:22`

### 2. New PHP 8.2 Classes

#### Logger System (Enum + Readonly Class)
**File:** `includes/Logger.php`

- **LogLevel Enum** (PHP 8.1+): Type-safe log levels
  ```php
  enum LogLevel: string {
      case DEBUG = 'DBG';
      case INFO = 'INF';
      case ERROR = 'ERR';
  }
  ```

- **SyncLogger Readonly Class** (PHP 8.2): Immutable logger configuration
  ```php
  readonly class SyncLogger {
      public function __construct(
          private string $logFile,
          private bool $debugEnabled = false,
          private bool $infoEnabled = true,
      ) {}
  }
  ```

**Benefits:**
- Type-safe log levels (can't misspell or use invalid levels)
- Immutable logger configuration (can't be accidentally modified)
- Cleaner API with dedicated methods: `debug()`, `info()`, `error()`

#### Configuration Management (Readonly Class)
**File:** `includes/SyncConfig.php`

- **SyncConfig Readonly Class** (PHP 8.2): Immutable configuration object
  ```php
  readonly class SyncConfig {
      public function __construct(
          public string $url,
          public string $apiToken,
          public array $calendarIds,
          public array $categories,
          public int $importPast,
          public int $importFuture,
          public int $resourceTypeForCategories = -1,
          public string $emImageAttr = '',
      ) {}
  }
  ```

**Benefits:**
- Eliminates global variable dependency for configuration
- Type-safe configuration (PHP knows the exact types)
- Immutable (can't be accidentally modified during execution)
- Factory methods for creating from different sources:
  - `fromPost()` - From POST data
  - `fromOptions()` - From WordPress options
  - `toArray()` - Convert to array for storage

**Helper Methods:**
- `getFromDate()` / `getToDate()` - Calculate date ranges
- `getCategoryMapping()` - Build calendar-to-category mapping

### 3. Code Modernization

#### Type Hints Throughout
Added proper type hints to all functions:

```php
// Before (PHP 8.0)
function logError($message) { ... }
function processCalendarEntry(Appointment $ctCalEntry, array $mapping, int $resourceType) { ... }

// After (PHP 8.2)
function logError(string $message): void { ... }
function processCalendarEntry(
    Appointment $ctCalEntry,
    array $calendars_categories_mapping,
    int $resourcetype_for_categories,
    SyncConfig $config
): void { ... }
```

**Functions Updated:**
- All logging functions (`logDebug`, `logInfo`, `logError`)
- `processCalendarEntry()` - Now accepts SyncConfig
- `getCreateLocation()` - Proper nullable return type
- `updateEventCategories()` - Nullable CombinedAppointment parameter
- `cleanupOldEntries()` - String type hints
- `downloadEventImage()` - Nullable return type
- `uploadFromLocalFile()` - Union type `int|false`
- `addFlyerLink()` - String parameters and return
- `get_attachment_id_by_filename()` - Union type `int|false`
- Migration functions - Array shape return types
- WordPress hooks - Void return types

#### String Interpolation
Replaced string concatenation with modern interpolation where appropriate:

```php
// Before
logInfo("Start sync cycle ".$startTimestamp);
logInfo("Searching calendar entries from ".$fromDate." until ".$toDate. " in calendars [".implode(",", $calendars)."]");

// After
logInfo("Start sync cycle {$startTimestamp}");
logInfo("Searching calendar entries from {$fromDate} until {$toDate} in calendars [" . implode(",", $calendars) . "]");
```

#### Removed serialize/unserialize
**Before:**
```php
$saved_data = $saved_data ? unserialize($saved_data) : null;
add_option('ctwpsync_options', serialize($data));
```

**After:**
```php
// WordPress handles serialization automatically
$config = SyncConfig::fromOptions($options);
update_option('ctwpsync_options', $data);
```

**Note:** WordPress `get_option()` and `update_option()` automatically handle serialization of arrays, so manual serialize/unserialize is no longer needed.

#### JSON Instead of serialize() for Logging
```php
// Before
logDebug("Categories mapping: ".serialize($calendars_categories_mapping));

// After
logDebug("Categories mapping: " . json_encode($calendars_categories_mapping));
```

**Benefits:**
- More readable in logs
- Safer (no serialization vulnerabilities)
- Compatible with modern tools

### 4. Consistency Improvements

#### Comparison Operators
```php
// Before: Loose comparison
if ($ctCalEntry->getRepeatId() != "0")
if ($appointmentAddress != null)
if (get_current_user_id() == 0)

// After: Strict comparison
if ($ctCalEntry->getRepeatId() !== "0")
if ($appointmentAddress !== null)
if (get_current_user_id() === 0)
```

#### Null Coalescing Operator
```php
// Before
$myPage = isset($_GET['page']) ? $_GET['page'] : "";

// After
$myPage = $_GET['page'] ?? '';
```

#### Named Arguments (PHP 8.0+)
Used where it improves readability:

```php
// Logger initialization
$ctwpsync_logger = new SyncLogger(
    logFile: plugin_dir_path(__FILE__) . 'wpcalsync.log',
    debugEnabled: false,
    infoEnabled: true
);

// Config from POST
return new self(
    url: rtrim(trim($_POST['ctwpsync_url']), '/') . '/',
    apiToken: trim($_POST['ctwpsync_apitoken']),
    calendarIds: self::parseCalendarIds($_POST['ctwpsync_ids']),
    // ... etc
);
```

## Migration Path for Existing Installations

The plugin maintains **backward compatibility** for data:

1. **Existing WordPress Options:** The plugin still reads from `ctwpsync_options` option. The new `SyncConfig` class provides a factory method `fromOptions()` to convert the old array format to the new immutable object.

2. **Database:** No database schema changes required. The sync mapping table remains unchanged.

3. **Saved Settings:** When settings are saved via the admin panel, they're still stored as arrays in WordPress options (backward compatible), but now use the `SyncConfig` class for validation and processing.

## Performance Impact

- **Readonly classes** are optimized by PHP's engine
- **Type hints** enable JIT optimizations
- **Less string concatenation** (interpolation is faster)
- Overall: Slight performance improvement expected

## Breaking Changes

⚠️ **Server Requirements:**
- Minimum PHP version changed from **8.0 to 8.2**
- Servers running PHP 8.0 or 8.1 will need to upgrade

## Testing Checklist

Before deployment, verify:

- [ ] Settings page loads correctly
- [ ] Settings can be saved successfully
- [ ] Manual sync works (Save settings triggers sync)
- [ ] Hourly cron sync executes
- [ ] Events are created/updated properly
- [ ] Event categories are assigned correctly
- [ ] Event images are handled (both embedded and downloaded)
- [ ] Event locations are created/found
- [ ] Flyers are attached correctly
- [ ] Migration functions work for EM 7.1+ and 7.2+
- [ ] Logging works (check `wpcalsync.log`)

## Files Modified

1. `churchtools-wpcalendarsync.php` - Main plugin file
2. `churchtools-dosync.php` - Sync logic
3. `includes/Logger.php` - NEW: Logger system
4. `includes/SyncConfig.php` - NEW: Configuration management

## Future Improvements (Optional)

With PHP 8.2 as the baseline, consider:

1. **PHP 8.3 Features** (if upgrade to 8.3):
   - Typed class constants
   - `json_validate()` for safer JSON handling
   - `#[\Override]` attribute for better refactoring safety

2. **Further Modernization**:
   - Convert more procedural code to OOP
   - Use dependency injection instead of globals
   - Add PHPStan/Psalm for static analysis
   - Add proper unit tests

3. **Logging Improvements**:
   - Make log level configurable from admin UI
   - Add log rotation
   - Display recent logs in admin dashboard

## Conclusion

This upgrade brings the plugin up to modern PHP standards while maintaining data compatibility. The use of readonly classes, enums, and proper type hints makes the code more maintainable, safer, and easier to understand.
