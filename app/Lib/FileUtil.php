<?php

/**
 * @author David Mertl <dave@onzra.com>
 */
class FileUtil {

	/**
	 * Get a unique filename in a directory by adding "_<number>" until there are no conflicts
	 * @param string $path
	 * @return string
	 */
	public static function incrementedUniqueFilename($path) {
		if(file_exists($path)) {
			if(strpos($path, '.') !== false) {
				$file = substr($path, 0, strpos($path, '.'));
				$ext = substr($path, strpos($path, '.'));
			} else {
				$file = $path;
				$ext = '';
			}
			$cnt = 1;
			$path = $file . '_' . $cnt . $ext;
			while(file_exists($path)) {
				$path = $file . '_' . $cnt . $ext;
				$cnt++;
			}
		}
		return $path;
	}

	/**
	 * Attempt to parse a filename out of a URL
	 * @param string $url
	 * @return string|bool
	 */
	public static function parseFilenameFromUrl($url) {
		if(($slash_pos = strrpos($url, '/')) !== false) {
			$query_pos = strpos($url, '?');
			$length = $query_pos ? $query_pos - $slash_pos - 1 : strlen($url) - $slash_pos - 1;
			if($filename = substr($url, $slash_pos + 1, $length)) {
				return $filename;
			}
		}
		return false;
	}

}
