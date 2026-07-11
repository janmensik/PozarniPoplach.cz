<?php

namespace PozarniPoplach;

class Version
{
    protected $filename = './CHANGELOG.md';
    public $versions = array();

    # ...................................................................
    # KONSTRUKTOR
    public function __construct(?string $filename = null)
    {
        if ($filename) {
            $this->filename = $filename;
        }

        $this->load();
        return (true);
    }

    # ...................................................................
    public function load(): bool
    {
        if (!file_exists($this->filename)) {
            return false;
        }

        $content = file_get_contents($this->filename);
        if (!$content) {
            return false;
        }

        // Split by version headers: ## [x.y.z] - YYYY-MM-DD
        $sections = preg_split('/^##\s*\[/m', $content, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($sections as $section) {
            // Extract version and date: 0.2.1] - 2025-12-08
            if (preg_match('/^(\d+\.\d+\.\d+)\]\s*-\s*(\d{4}-\d{2}-\d{2})/', $section, $matches)) {
                $version = $matches[1];
                $date = $matches[2];

                $this->versions[$version] = array(
                    'version' => $version,
                    'date' => $date,
                    'date_ts' => strtotime($date),
                    'data' => array()
                );

                // Process subsections (Added, Changed, Fixed, etc.)
                $subsections = preg_split('/^###\s*/m', $section, -1, PREG_SPLIT_NO_EMPTY);
                // First element is the header we already parsed
                array_shift($subsections);

                foreach ($subsections as $sub) {
                    $lines = explode("\n", $sub);
                    $type = strtolower(trim(array_shift($lines)));

                    // Map Markdown types to legacy keys for compatibility
                    $key = 'change';
                    if ($type === 'added') {
                        $key = 'new';
                    } elseif ($type === 'fixed') {
                        $key = 'bugfix';
                    } elseif ($type === 'changed') {
                        $key = 'change';
                    }
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // Match list items starting with -, +, or *
                        if (preg_match('/^[\-\+\*]\s+(.*)/', $line, $itemMatch)) {
                            $this->versions[$version]['data'][$key][] = $itemMatch[1];
                        }
                    }
                }
            }
        }

        krsort($this->versions, SORT_NATURAL);
        return true;
    }

    # ...................................................................
    public function getCurrentVersion(): string
    {
        if (empty($this->versions)) {
            return '0.0.0';
        }
        $temp = reset($this->versions);
        return ($temp['version']);
    }
}
