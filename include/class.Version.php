<?php
# ěščřžýáíéů
class Version {
	var $filename = './version.txt';
	var $versions;

	# ...................................................................
	# KONSTRUKTOR
	public function __construct($filename = null) {
		if ($filename)
			$this->filename = $filename;

		$this->load();
		return (true);
	}

	# ...................................................................	
	function load() {
		$handle = fopen($this->filename, "r");

		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$this->processLine($line, $version_lines);
			}
			fclose($handle);

			$this->processImport($version_lines);
			krsort($this->versions, SORT_NATURAL);
		} else {
			return (false);
		}
	}

	# ...................................................................	
	function processImport($input) {
		foreach ($input as $item) {
			$key = key($item);
			$value = current($item);

			if ($key == 'version')
				$version = $value;
			if ($version) {
				if (in_array($key, array('version', 'date', 'date_ts')))
					$this->versions[$version][$key] = $value;
				else
					$this->versions[$version]['data'][$key][] = $value;
			}
		}
		return ($this->versions);
	}

	# ...................................................................	
	function processLine($line, &$output) {
		$line = trim($line);
		preg_match('/^\s*(\S){1}/i', $line, $matches);
		if ($matches) {
			switch ($matches[1]) {
					# doc
				case '#':
					return (false);
					break;
					# change
				case '-':
					preg_match('/^\s*\-\s*(\S+.*)/i', $line, $match);
					$output[]['change'] = trim($match[1]);
					break;
					# new
				case '+':
					preg_match('/^\s*\+\s*(\S+.*)/i', $line, $match);
					$output[]['new'] = trim($match[1]);
					break;
					# bugfix
				case '*':
					preg_match('/^\s*\*\s*(\S+.*)/i', $line, $match);
					$output[]['bugfix'] = trim($match[1]);
					break;
					# version
				default:
					preg_match('/^\s*((\d+\.)*\d+)+\s*\/\s*(\S+.*)/ui', $line, $match);
					$output[]['version'] = trim($match[1]);
					$output[]['date'] = trim($match[3]);
					$output[]['date_ts'] = strtotime($match[3]);
			}
		}
	}

	# ...................................................................	
	function getCurrentVersion() {
		$temp = reset($this->versions);
		return ($temp['version']);
	}
}
