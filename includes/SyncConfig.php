<?php
/**
 * Configuration management for ChurchTools WP Calendar Sync
 *
 * @package Ctwpsync
 */

/**
 * Immutable configuration for sync operations
 *
 * Uses PHP 8.2 readonly class to ensure configuration cannot be modified after creation
 */
readonly class SyncConfig {
    /**
     * Create a new sync configuration
     *
     * @param string $url ChurchTools URL
     * @param string $apiToken API authentication token
     * @param array $calendarIds Array of calendar IDs to sync
     * @param array $categories Categories mapped to calendar IDs
     * @param int $importPast Days in the past to import
     * @param int $importFuture Days in the future to import
     * @param int $resourceTypeForCategories Resource type ID for category mapping (-1 to disable)
     * @param string $emImageAttr Events Manager custom attribute for image URLs (empty to disable)
     */
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

    /**
     * Create configuration from POST data
     *
     * @return self
     */
    public static function fromPost(): self {
        return new self(
            url: rtrim(trim($_POST['ctwpsync_url']), '/') . '/',
            apiToken: trim($_POST['ctwpsync_apitoken']),
            calendarIds: self::parseCalendarIds($_POST['ctwpsync_ids']),
            categories: self::parseCategories($_POST['ctwpsync_ids_categories']),
            importPast: (int)trim($_POST['ctwpsync_import_past']),
            importFuture: (int)trim($_POST['ctwpsync_import_future']),
            resourceTypeForCategories: (int)trim($_POST['ctwpsync_resourcetype_for_categories']),
            emImageAttr: trim($_POST['ctwpsync_em_image_attr']),
        );
    }

    /**
     * Create configuration from stored options array
     *
     * @param array $options Stored options from WordPress
     * @return self|null
     */
    public static function fromOptions(array $options): ?self {
        if (empty($options['url']) || empty($options['apitoken'])) {
            return null;
        }

        return new self(
            url: $options['url'],
            apiToken: $options['apitoken'],
            calendarIds: $options['ids'] ?? [],
            categories: $options['ids_categories'] ?? [],
            importPast: $options['import_past'] ?? 0,
            importFuture: $options['import_future'] ?? 380,
            resourceTypeForCategories: $options['resourcetype_for_categories'] ?? -1,
            emImageAttr: $options['em_image_attr'] ?? '',
        );
    }

    /**
     * Convert configuration to array for storage
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'url' => $this->url,
            'apitoken' => $this->apiToken,
            'ids' => $this->calendarIds,
            'ids_categories' => $this->categories,
            'import_past' => $this->importPast,
            'import_future' => $this->importFuture,
            'resourcetype_for_categories' => $this->resourceTypeForCategories,
            'em_image_attr' => $this->emImageAttr,
        ];
    }

    /**
     * Parse calendar IDs from input string
     *
     * @param string $ids Comma-separated calendar IDs
     * @return array Array of integer calendar IDs
     */
    private static function parseCalendarIds(string $ids): array {
        $result = [];
        foreach (preg_split('/\D/', $ids) as $id) {
            $intId = intval($id);
            if ($intId > 0) {
                $result[] = $intId;
            }
        }
        return $result;
    }

    /**
     * Parse categories from input string
     *
     * @param string $categories Comma-separated categories
     * @return array Array of category strings
     */
    private static function parseCategories(string $categories): array {
        $result = [];
        foreach (preg_split('/,/', $categories) as $category) {
            $result[] = trim($category);
        }
        return $result;
    }

    /**
     * Get the from date for sync (based on importPast)
     *
     * @return string Date string in Y-m-d format
     */
    public function getFromDate(): string {
        if ($this->importPast < 0) {
            return date('Y-m-d', strtotime('+' . ($this->importPast * -1) . ' days'));
        }
        return date('Y-m-d', strtotime('-' . $this->importPast . ' days'));
    }

    /**
     * Get the to date for sync (based on importFuture)
     *
     * @return string Date string in Y-m-d format
     */
    public function getToDate(): string {
        if ($this->importFuture < 0) {
            return date('Y-m-d', strtotime('-' . ($this->importFuture * -1) . ' days'));
        }
        return date('Y-m-d', strtotime('+' . $this->importFuture . ' days'));
    }

    /**
     * Get calendar categories mapping array
     *
     * Maps calendar ID to category name
     *
     * @return array Associative array of calendar ID => category name
     */
    public function getCategoryMapping(): array {
        $mapping = [];
        $i = 0;
        while ($i < count($this->calendarIds)) {
            if ($i < count($this->categories)) {
                $mapping[$this->calendarIds[$i]] = $this->categories[$i] ?? null;
            } else {
                $mapping[$this->calendarIds[$i]] = null;
            }
            $i++;
        }
        return $mapping;
    }
}
