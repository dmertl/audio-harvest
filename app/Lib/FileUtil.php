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
	public static function uniqueFilename($path) {
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

}
