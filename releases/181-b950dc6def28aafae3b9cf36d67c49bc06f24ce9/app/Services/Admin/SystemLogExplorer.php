<?php

namespace App\Services\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class SystemLogExplorer
{
    private const DEFAULT_LIMIT = 120;

    private const MAX_LIMIT = 300;

    public function snapshot(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $channels = $this->channelDefinitions();
        $channelFiles = [];

        foreach ($channels as $key => $channel) {
            $channelFiles[$key] = $this->findFilesForPatterns($channel['patterns']);
        }

        [$visibleFiles, $filesToRead] = $this->resolveFiles(
            source: $normalized['source'],
            selectedFile: $normalized['file'],
            channels: $channels,
            channelFiles: $channelFiles,
        );

        $entries = $this->collectEntries(
            filesToRead: $filesToRead,
            channels: $channels,
            filters: $normalized,
        );

        $selectedChannel = $normalized['source'] !== 'all'
            ? ($channels[$normalized['source']] ?? null)
            : null;

        return [
            'filters' => $normalized,
            'channels' => $this->buildChannelSummaries(
                channels: $channels,
                channelFiles: $channelFiles,
                activeSource: $normalized['source'],
            ),
            'selected_channel' => $selectedChannel ? [
                'key' => $normalized['source'],
                'label' => $selectedChannel['label'],
                'description' => $selectedChannel['description'],
                'icon' => $selectedChannel['icon'],
            ] : null,
            'files' => $visibleFiles,
            'entries' => $entries,
            'summary' => $this->buildSummary($entries),
            'level_options' => $this->levelOptions(),
            'generated_at' => now()->toIso8601String(),
            'log_directory' => $this->logDirectory(),
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, icon: string, patterns: array<int, string>}>
     */
    private function channelDefinitions(): array
    {
        return [
            'application' => [
                'label' => 'Application',
                'description' => 'Laravel framework exceptions, stack traces, and general runtime output.',
                'icon' => 'fas fa-layer-group',
                'patterns' => ['laravel.log'],
            ],
            'payments' => [
                'label' => 'Payments',
                'description' => 'STK pushes, callbacks, reconciliations, and payment activation flow.',
                'icon' => 'fas fa-credit-card',
                'patterns' => ['payment-*.log'],
            ],
            'mikrotik' => [
                'label' => 'MikroTik',
                'description' => 'Router API calls, hotspot activation attempts, and connectivity failures.',
                'icon' => 'fas fa-router',
                'patterns' => ['mikrotik-*.log'],
            ],
            'radius' => [
                'label' => 'RADIUS',
                'description' => 'Provisioning, accounting, disconnects, and authorization issues.',
                'icon' => 'fas fa-satellite-dish',
                'patterns' => ['radius-*.log'],
            ],
            'security' => [
                'label' => 'Security',
                'description' => 'Authentication, rate limits, abuse checks, and suspicious activity.',
                'icon' => 'fas fa-shield-halved',
                'patterns' => ['security-*.log'],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'SMS, email, and customer communication delivery events.',
                'icon' => 'fas fa-bell',
                'patterns' => ['notification-*.log'],
            ],
            'errors' => [
                'label' => 'Errors',
                'description' => 'Dedicated high-priority exception stream for critical failures.',
                'icon' => 'fas fa-triangle-exclamation',
                'patterns' => ['error-*.log'],
            ],
            'scheduler' => [
                'label' => 'Scheduler',
                'description' => 'Command outputs from session expiry checks, reconciliations, and router health.',
                'icon' => 'fas fa-clock-rotate-left',
                'patterns' => ['sessions-check.log', 'payment-reconciliation.log', 'router-health.log'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{source: string, level: string, search: string, limit: int, file: ?string}
     */
    private function normalizeFilters(array $filters): array
    {
        $channels = $this->channelDefinitions();
        $source = (string) ($filters['source'] ?? 'all');
        if ($source !== 'all' && !array_key_exists($source, $channels)) {
            $source = 'all';
        }

        $level = strtolower((string) ($filters['level'] ?? 'all'));
        if (!array_key_exists($level, $this->levelOptions())) {
            $level = 'all';
        }

        $limit = max(20, min(self::MAX_LIMIT, (int) ($filters['limit'] ?? self::DEFAULT_LIMIT)));

        $file = trim((string) ($filters['file'] ?? ''));
        if ($file === '') {
            $file = null;
        }

        return [
            'source' => $source,
            'level' => $level,
            'search' => trim((string) ($filters['search'] ?? '')),
            'limit' => $limit,
            'file' => $file,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function levelOptions(): array
    {
        return [
            'all' => 'All levels',
            'debug' => 'Debug',
            'info' => 'Info',
            'notice' => 'Notice',
            'warning' => 'Warning',
            'error' => 'Error',
            'critical' => 'Critical',
            'alert' => 'Alert',
            'emergency' => 'Emergency',
        ];
    }

    /**
     * @param array<int, string> $patterns
     * @return array<int, array<string, mixed>>
     */
    private function findFilesForPatterns(array $patterns): array
    {
        $files = [];
        $seen = [];

        foreach ($patterns as $pattern) {
            $matches = glob($this->logDirectory() . DIRECTORY_SEPARATOR . $pattern) ?: [];

            foreach ($matches as $path) {
                $realPath = realpath($path) ?: $path;
                if (!is_file($realPath) || isset($seen[$realPath])) {
                    continue;
                }

                $seen[$realPath] = true;
                $modifiedAt = @filemtime($realPath) ?: null;
                $size = @filesize($realPath);
                $files[] = [
                    'path' => $realPath,
                    'name' => basename($realPath),
                    'size_bytes' => $size === false ? 0 : (int) $size,
                    'size_label' => $this->formatBytes($size === false ? 0 : (int) $size),
                    'last_modified_at' => $modifiedAt ? CarbonImmutable::createFromTimestamp($modifiedAt)->toIso8601String() : null,
                    'last_modified_label' => $modifiedAt
                        ? CarbonImmutable::createFromTimestamp($modifiedAt)
                            ->setTimezone(config('app.timezone', 'UTC'))
                            ->format('d M Y, H:i:s')
                        : 'Unknown',
                    'last_modified_unix' => $modifiedAt ?: 0,
                ];
            }
        }

        usort($files, static function (array $left, array $right): int {
            return ($right['last_modified_unix'] <=> $left['last_modified_unix'])
                ?: strcmp((string) $left['name'], (string) $right['name']);
        });

        return $files;
    }

    /**
     * @param array<string, array<string, mixed>> $channels
     * @param array<string, array<int, array<string, mixed>>> $channelFiles
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function resolveFiles(string $source, ?string $selectedFile, array $channels, array $channelFiles): array
    {
        if ($source === 'all') {
            $visibleFiles = [];

            foreach ($channels as $key => $channel) {
                $file = $channelFiles[$key][0] ?? null;
                if (!$file) {
                    continue;
                }

                $visibleFiles[] = [
                    ...$file,
                    'channel' => $key,
                    'channel_label' => $channel['label'],
                    'selected' => true,
                ];
            }

            return [$visibleFiles, $visibleFiles];
        }

        $visibleFiles = [];
        $files = $channelFiles[$source] ?? [];
        $resolvedSelection = null;

        foreach ($files as $index => $file) {
            $isSelected = $selectedFile !== null
                ? ((string) $file['name'] === $selectedFile)
                : ($index === 0);

            if ($isSelected && $resolvedSelection === null) {
                $resolvedSelection = [
                    ...$file,
                    'channel' => $source,
                    'channel_label' => $channels[$source]['label'],
                    'selected' => true,
                ];
            }

            $visibleFiles[] = [
                ...$file,
                'channel' => $source,
                'channel_label' => $channels[$source]['label'],
                'selected' => $isSelected,
            ];
        }

        if ($resolvedSelection === null && isset($visibleFiles[0])) {
            $visibleFiles[0]['selected'] = true;
            $resolvedSelection = $visibleFiles[0];
        }

        return [$visibleFiles, $resolvedSelection ? [$resolvedSelection] : []];
    }

    /**
     * @param array<string, array<string, mixed>> $channels
     * @param array<string, array<int, array<string, mixed>>> $channelFiles
     * @return array<int, array<string, mixed>>
     */
    private function buildChannelSummaries(array $channels, array $channelFiles, string $activeSource): array
    {
        $summaries = [
            [
                'key' => 'all',
                'label' => 'All sources',
                'description' => 'Latest file from each operational channel.',
                'icon' => 'fas fa-wave-square',
                'file_count' => array_sum(array_map(static fn (array $files): int => count($files), $channelFiles)),
                'latest_file' => null,
                'last_updated_at' => null,
                'last_updated_label' => 'Mixed',
                'active' => $activeSource === 'all',
            ],
        ];

        foreach ($channels as $key => $channel) {
            $latest = $channelFiles[$key][0] ?? null;

            $summaries[] = [
                'key' => $key,
                'label' => $channel['label'],
                'description' => $channel['description'],
                'icon' => $channel['icon'],
                'file_count' => count($channelFiles[$key] ?? []),
                'latest_file' => $latest['name'] ?? null,
                'last_updated_at' => $latest['last_modified_at'] ?? null,
                'last_updated_label' => $latest['last_modified_label'] ?? 'No log file yet',
                'active' => $activeSource === $key,
            ];
        }

        return $summaries;
    }

    /**
     * @param array<int, array<string, mixed>> $filesToRead
     * @param array<string, array<string, mixed>> $channels
     * @param array{source: string, level: string, search: string, limit: int, file: ?string} $filters
     * @return array<int, array<string, mixed>>
     */
    private function collectEntries(array $filesToRead, array $channels, array $filters): array
    {
        $entries = [];
        $linesPerFile = max(80, min(260, $filters['limit'] * 3));

        foreach ($filesToRead as $file) {
            $lines = $this->tailLines((string) $file['path'], $linesPerFile);

            foreach ($this->parseEntries($lines, $file, $channels) as $entry) {
                if (!$this->matchesLevel($entry, $filters['level'])) {
                    continue;
                }

                if (!$this->matchesSearch($entry, $filters['search'])) {
                    continue;
                }

                $entries[] = $entry;
            }
        }

        usort($entries, static function (array $left, array $right): int {
            return ($right['sort_unix'] <=> $left['sort_unix'])
                ?: strcmp((string) $right['message'], (string) $left['message']);
        });

        return array_slice($entries, 0, $filters['limit']);
    }

    /**
     * @param array<int, string> $lines
     * @param array<string, mixed> $file
     * @param array<string, array<string, mixed>> $channels
     * @return array<int, array<string, mixed>>
     */
    private function parseEntries(array $lines, array $file, array $channels): array
    {
        $entries = [];
        $current = null;
        $source = (string) ($file['channel'] ?? 'application');
        $sourceLabel = (string) ($file['channel_label'] ?? ($channels[$source]['label'] ?? Str::headline($source)));

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }

            $parsed = $this->parseStructuredLine($line, $file, $source, $sourceLabel);
            if ($parsed !== null) {
                if ($current !== null) {
                    $entries[] = $this->finalizeEntry($current);
                }

                $current = $parsed;
                continue;
            }

            if ($current === null) {
                $current = $this->makeFallbackEntry($line, $file, $source, $sourceLabel);
                continue;
            }

            $current['details'][] = $line;
        }

        if ($current !== null) {
            $entries[] = $this->finalizeEntry($current);
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>|null
     */
    private function parseStructuredLine(string $line, array $file, string $source, string $sourceLabel): ?array
    {
        $patterns = [
            '/^\[(?<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<environment>[A-Za-z0-9_.-]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/',
            '/^\[(?<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<level>[A-Z]+):\s*(?<message>.*)$/',
            '/^\[(?<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*(?<message>.*)$/',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $line, $matches)) {
                continue;
            }

            $timestamp = $this->resolveTimestamp((string) ($matches['timestamp'] ?? ''));
            [$message, $context, $contextPretty] = $this->splitContextFromMessage((string) ($matches['message'] ?? ''));
            $level = strtolower((string) ($matches['level'] ?? $this->inferLevelFromText($message)));

            return [
                'source' => $source,
                'source_label' => $sourceLabel,
                'file' => (string) $file['name'],
                'environment' => (string) ($matches['environment'] ?? ''),
                'message' => $message !== '' ? $message : 'Log entry',
                'level' => $level,
                'level_label' => Str::headline($level),
                'level_rank' => $this->levelRank($level),
                'timestamp' => $timestamp?->toIso8601String(),
                'timestamp_label' => $timestamp
                    ? $timestamp->setTimezone(config('app.timezone', 'UTC'))->format('d M Y, H:i:s')
                    : ($file['last_modified_label'] ?? 'Unknown'),
                'sort_unix' => $timestamp?->getTimestamp() ?? (int) ($file['last_modified_unix'] ?? 0),
                'context' => $context,
                'context_pretty' => $contextPretty,
                'details' => [],
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    private function makeFallbackEntry(string $line, array $file, string $source, string $sourceLabel): array
    {
        $level = $this->inferLevelFromText($line);

        return [
            'source' => $source,
            'source_label' => $sourceLabel,
            'file' => (string) $file['name'],
            'environment' => '',
            'message' => $line,
            'level' => $level,
            'level_label' => Str::headline($level),
            'level_rank' => $this->levelRank($level),
            'timestamp' => null,
            'timestamp_label' => $file['last_modified_label'] ?? 'Unknown',
            'sort_unix' => (int) ($file['last_modified_unix'] ?? 0),
            'context' => null,
            'context_pretty' => null,
            'details' => [],
        ];
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function finalizeEntry(array $entry): array
    {
        $details = implode("\n", $entry['details'] ?? []);
        unset($entry['details']);

        $entry['details_pretty'] = $details !== '' ? $details : null;

        return $entry;
    }

    /**
     * @return array{0: string, 1: array<mixed>|string|null, 2: string|null}
     */
    private function splitContextFromMessage(string $message): array
    {
        foreach ([' {', ' ['] as $needle) {
            $position = strrpos($message, $needle);
            if ($position === false) {
                continue;
            }

            $json = substr($message, $position + 1);
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $headline = trim(substr($message, 0, $position));

            return [
                $headline,
                $decoded,
                json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ];
        }

        return [$message, null, null];
    }

    private function resolveTimestamp(string $timestamp): ?CarbonImmutable
    {
        if ($timestamp === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($timestamp, config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function matchesLevel(array $entry, string $level): bool
    {
        if ($level === 'all') {
            return true;
        }

        return (string) ($entry['level'] ?? 'info') === $level;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function matchesSearch(array $entry, string $search): bool
    {
        if ($search === '') {
            return true;
        }

        $haystacks = [
            (string) ($entry['message'] ?? ''),
            (string) ($entry['source_label'] ?? ''),
            (string) ($entry['file'] ?? ''),
            (string) ($entry['environment'] ?? ''),
            (string) ($entry['context_pretty'] ?? ''),
            (string) ($entry['details_pretty'] ?? ''),
        ];

        foreach ($haystacks as $haystack) {
            if (Str::contains(Str::lower($haystack), Str::lower($search))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, mixed>
     */
    private function buildSummary(array $entries): array
    {
        $criticalLevels = ['critical', 'alert', 'emergency'];
        $errorLevels = ['error', ...$criticalLevels];
        $warningLevels = ['warning'];
        $latest = $entries[0] ?? null;

        return [
            'total' => count($entries),
            'critical' => count(array_filter($entries, static fn (array $entry): bool => in_array((string) $entry['level'], $criticalLevels, true))),
            'errors' => count(array_filter($entries, static fn (array $entry): bool => in_array((string) $entry['level'], $errorLevels, true))),
            'warnings' => count(array_filter($entries, static fn (array $entry): bool => in_array((string) $entry['level'], $warningLevels, true))),
            'channels' => count(array_unique(array_map(static fn (array $entry): string => (string) ($entry['source'] ?? ''), $entries))),
            'latest_event_at' => $latest['timestamp'] ?? null,
            'latest_event_label' => $latest['timestamp_label'] ?? 'No events found',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function tailLines(string $path, int $maxLines): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $leftover = '';
        $position = @filesize($path);
        $position = $position === false ? 0 : (int) $position;
        $chunkSize = 8192;
        $lines = [];

        while ($position > 0 && count($lines) <= $maxLines) {
            $readSize = min($chunkSize, $position);
            $position -= $readSize;
            fseek($handle, $position);
            $chunk = (string) fread($handle, $readSize);
            $buffer = $chunk . $leftover;
            $lines = preg_split("/\r\n|\n|\r/", $buffer) ?: [];

            if ($position > 0) {
                $leftover = array_shift($lines) ?? '';
            } else {
                $leftover = '';
            }
        }

        fclose($handle);

        if ($leftover !== '') {
            array_unshift($lines, $leftover);
        }

        $lines = array_values(array_filter($lines, static fn ($line): bool => $line !== null));

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, -$maxLines);
        }

        return $lines;
    }

    private function inferLevelFromText(string $text): string
    {
        $text = Str::lower($text);

        if (Str::contains($text, ['emergency'])) {
            return 'emergency';
        }

        if (Str::contains($text, ['alert'])) {
            return 'alert';
        }

        if (Str::contains($text, ['critical', 'panic'])) {
            return 'critical';
        }

        if (Str::contains($text, ['error', 'exception', 'failed', 'timeout'])) {
            return 'error';
        }

        if (Str::contains($text, ['warn', 'retry', 'degraded'])) {
            return 'warning';
        }

        if (Str::contains($text, ['notice'])) {
            return 'notice';
        }

        if (Str::contains($text, ['debug'])) {
            return 'debug';
        }

        return 'info';
    }

    private function levelRank(string $level): int
    {
        return match ($level) {
            'debug' => 10,
            'info' => 20,
            'notice' => 30,
            'warning' => 40,
            'error' => 50,
            'critical' => 60,
            'alert' => 70,
            'emergency' => 80,
            default => 20,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes) . ' B';
    }

    private function logDirectory(): string
    {
        return (string) config('admin.logs.path', storage_path('logs'));
    }
}
