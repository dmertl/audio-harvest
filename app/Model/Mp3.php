<?php

App::uses('AppModel', 'Model');
App::uses('HarvestHttpSocket', 'Lib');
App::import('Vendor', 'getid3/getid3');

/**
 * Mp3 Model
 * @property Link $Link
 */
class Mp3 extends AppModel {

	/**
	 * @var string
	 */
	public $displayField = 'name';

	/**
	 * @var array
	 */
	public $belongsTo = array('Link' => array());

	/**
	 * @var string
	 */
	protected $downloadPath;

	/**
	 * @var HarvestHttpSocket
	 */
	protected $httpSocket;

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->downloadPath = APP_PATH . 'downloads/';
		$this->httpSocket = new HarvestHttpSocket();
	}

	/**
	 * Download all undownloaded mp3s
	 */
	public function downloadAll() {
		$mp3s = $this->Mp3->find('all', array(
				'conditions' => array(
					'Mp3.downloaded' => null,
					'Mp3.error' => null
				),
				'recursive' => -1
			)
		);
		CakeLog::write('scrape', 'Downloading ' . count($mp3s) . ' mp3s.');
		foreach($mp3s as $mp3) {
			$this->download($mp3);
		}
		CakeLog::write('scrape', 'Downloaded ' . count($mp3s) . ' mp3s.');
	}

	/**
	 * Download an mp3
	 * TODO: filename can be null, should generate a unique filename in Link scrape
	 * @param array $mp3
	 */
	public function download($mp3) {
		CakeLog::write('scrape', 'Downloading ' . $mp3['Mp3']['url']);
		//Check if file exists
		if(!$this->fileExists($mp3['Mp3']['filename'])) {
			try {
				if($data = $this->httpSocket->get($mp3['Mp3']['url'])) {
					//Update Mp3 with response data
					$mp3 = $this->updateMp3FromResponse($mp3, $data, $this->httpSocket->getLastHeader('Content-Disposition'));
					CakeLog::write('scrape', 'Downloaded ' . $mp3['Mp3']['filename'] . '(' . $mp3['Mp3']['url'] . ')');
					//Check if hash already exists before saving file
					if(!$this->hashExists($mp3['Mp3']['hash'])) {
						if($filename = $this->getUniqueFilename(APP_PATH . 'downloads/' . $mp3['Mp3']['filename'])) {
							if(!file_put_contents($filename, $data)) {
								CakeLog::write('error', 'Unable to save file to ' . APP_PATH . 'downloads/' . $mp3['Mp3']['filename'] . ' for mp3 ' . $mp3['Mp3']['id']);
							}
							//Remove if duplicate file
							$this->removeIfDuplicate($filename, $mp3['Mp3']['filename']);
						} else {
							CakeLog::write('scrape', 'Unable to get duplicate filename for ' . APP_PATH . 'downloads/' . $mp3['Mp3']['filename']);
						}
					} else {
						CakeLog::write('scrape', 'Found duplicate mp3 by hash ' . $mp3['Mp3']['hash'] . ' for ' . $mp3['Mp3']['id']);
					}
				} else {
					CakeLog::write('scrape', 'Unable to download mp3 from ' . $mp3['Mp3']['url']);
				}
			} catch(FeedResponseException $e) {
				CakeLog::write('scrape', 'Unable to download mp3 from ' . $mp3['Mp3']['url'] . '. Error: ' . $e);
				$mp3['Mp3']['error'] = $e->getCode();
			}
			//Save Mp3
			$this->Mp3->create();
			if(!$this->Mp3->save($mp3)) {
				CakeLog::write('scrape', 'Error saving mp3 ' . $mp3['Mp3']['id']);
			}
		} else {
			CakeLog::write('scrape', 'Found duplicate mp3 for ' . $mp3['Mp3']['filename'] . ' by filename');
		}
	}

	/**
	 * Check if a file already exists at the path specified
	 * @param string $filename
	 * @return bool
	 */
	protected function fileExists($filename) {
		return $filename ? file_exists(APP_PATH . 'downloads/' . $filename) : false;
	}

	/**
	 * Update Mp3 model from response data
	 * @param array $mp3 Array of Mp3 model data
	 * @param string $data Raw mp3 data
	 * @param string|null $content_disposition Content-Disposition header from response
	 * @return array
	 */
	protected function updateMp3FromResponse($mp3, $data, $content_disposition = null) {
		$mp3['Mp3']['hash'] = md5($data);
		$mp3['Mp3']['size'] = strlen($data);
		$mp3['Mp3']['downloaded'] = date('Y-m-d H:i:s');
		//Pull filename from Content-Disposition header if available
		if($content_disposition) {
			$content_disposition = $this->httpSocket->parseContentDisposition($content_disposition);
			if(isset($content_disposition['params']['filename'])) {
				$mp3['Mp3']['filename'] = $content_disposition['params']['filename'];
			}
		}
		return $mp3;
	}

	/**
	 * Check if mp3 hash already exists in the database
	 * @param string $hash Hash of mp3 data
	 * @return bool
	 */
	public function hashExists($hash) {
		return $this->find('count', array('conditions' => array('Mp3.hash' => $hash))) > 0;
	}

	/**
	 * Get a unique filename
	 * @param string $path
	 * @param string $dup
	 * @return bool|string
	 */
	protected function getUniqueFilename($path, $dup = '') {
		if(is_numeric($dup) && $dup >= 40) {
			return false;
		}
		$dup_name = substr($path, 0, strrpos($path, '.')) . $dup . substr($path, strrpos($path, '.'));
		if(file_exists($dup_name)) {
			return $this->getUniqueFilename($path, ($dup == '' ? 1 : $dup + 1));
		} else {
			return $dup_name;
		}
	}

	/**
	 * Get mp3 information
	 * @param string $file
	 * @return array
	 */
	function getBasicInfo($file) {
		static $id3_cache;
		if(!$id3_cache) $id3_cache = array();
		if(isset($id3_cache[$file])) {
			return $id3_cache[$file];
		} else {
			$info = array();
			if(file_exists($file)) {
				$getID3 = new getID3();
				$id3 = @$getID3->analyze($file);

				if(!empty($id3['tags']['id3v2'])) {
					$data = $id3['tags']['id3v2'];
				} elseif(!empty($id3['tags']['id3v1'])) {
					$data = $id3['tags']['id3v1'];
				} else {
					$data = null;
				}

				//Playtime
				if(isset($id3['playtime_seconds'])) $info['playtime_seconds'] = $id3['playtime_seconds'];
				//Bitrate
				if(isset($id3['audio']['bitrate'])) $info['bitrate'] = $id3['audio']['bitrate'];

				//id3 tag data
				if(!empty($data)) {
					$info['artist'] = null;
					if(isset($data['artist'])) $info['artist'] = current($data['artist']);

					$info['title'] = null;
					if(isset($data['title'])) $info['title'] = current($data['title']);

					$info['album'] = null;
					if(isset($data['album'])) $info['album'] = current($data['album']);

					$id3_cache[$file] = $info;
				} else {
					$error_string = 'Unable to get id3 data for file: ' . $file;
					if(is_array($id3)) {
						$error_string .= "\n" . 'Keys: ' . "\n" . print_r(array_keys($id3), true);
					}
					if(isset($id3['tags']) && is_array($id3['tags'])) {
						$error_string .= "\n" . 'Tags: ' . "\n" . print_r(array_keys($id3['tags']), true);
					}
					CakeLog::write('error', $error_string);
				}
			} else {
				CakeLog::write('error', 'Trying to get id3 info of file that does not exist, "' . $file . '"');
			}

			return $info;
		}
	}

	function removeIfDuplicate($file) {
		if(file_exists($file)) {
			//Parse out new file name
			$new_filename = substr($file, strrpos($file, DS) + 1);

			//Get new file id3v2 info
			$new_info = $this->Mp3->getBasicInfo($file);

			if(!empty($new_info['artist'])) {
				//Check downloads folder
				$downloads_folder = new Folder(APP_PATH . 'downloads');
				$download_items = $downloads_folder->read(false);
				foreach($download_items[1] as $item) {
					//Ignore hidden files and new file
					if($item[0] != '.' && $item !== $new_filename) {
						$info = $this->Mp3->getBasicInfo(APP_PATH . 'downloads' . DS . $item);
						if($this->isDuplicate($info, $new_info)) {
							if($this->bitrateCompare($new_info, $info) > 0) {
								echo 'Removing ' . $item . ' because we downloaded ' . $new_filename . ' which has a better bitrate' . "\n";
								CakeLog::write('debug', 'Removing ' . $item . ' because we downloaded ' . $new_filename . ' which has a better bitrate');
								unlink($downloads_folder . DS . $item);
							} else {
								echo 'Removing ' . $new_filename . ' duplicate of ' . $item . ' in downloads' . "\n";
								CakeLog::write('debug', 'Removing ' . $new_filename . ' duplicate of ' . $item . ' in downloads');
								unlink($file);
								return;
							}
						}
					}
				}

				//Check iTunes library, assumes library is organized by iTunes
				$itunes_folder = new Folder(Configure::read('Mp3.itunes_folder') . DS . $new_info['artist']);
				$artist_items = $itunes_folder->read(false);
				//Loop through all album folders
				foreach($artist_items[0] as $album) {
					$album_folder = new Folder(Configure::read('Mp3.itunes_folder') . DS . $new_info['artist'] . DS . $album);
					$album_items = $album_folder->read(false);
					//Loop through all songs
					foreach($album_items[1] as $mp3) {
						if($mp3[0] != '.') {
							$info = $this->Mp3->getBasicInfo(Configure::read('Mp3.itunes_folder') . DS . $new_info['artist'] . DS . $album . DS . $mp3);
							if($this->isDuplicate($info, $new_info)) {
								if($this->bitrateCompare($new_info, $info) > 0) {
									echo 'Keeping ' . $new_filename . ' because it has better bitrate than ' . $mp3 . ' in iTunes library' . "\n";
									CakeLog::write('debug', 'Keeping ' . $new_filename . ' because it has better bitrate than ' . $mp3 . ' in iTunes library');
								} else {
									echo 'Removing ' . $new_filename . ' duplicate of ' . $mp3 . ' in iTunes library' . "\n";
									CakeLog::write('debug', 'Removing ' . $new_filename . ' duplicate of ' . $mp3 . ' in iTunes library');
									unlink($file);
									return;
								}
							}
						}
					}
				}
			} else {
				CakeLog::write('debug', 'Unable to get id3v2 info for ' . $new_filename);
			}
		}
	}

	function isDuplicate($a, $b) {
		if(!empty($a) && !empty($b)) {
			if(isset($a['artist']) && isset($a['title']) && isset($a['playtime_seconds']) && isset($b['artist']) && isset($b['title']) && isset($b['playtime_seconds'])) {
				if($a['artist'] == $b['artist'] && $a['title'] == $b['title'] && $a['playtime_seconds'] == $b['playtime_seconds']) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns bitrate a - bitrate b if available, otherwise false
	 * Positive = a > b
	 * Negative = a < b
	 * 0 = a == b
	 * @param $a
	 * @param $b
	 * @return bool|int
	 */
	public function bitrateCompare($a, $b) {
		if(isset($a['bitrate']) && isset($b['bitrate']) && is_numeric($a['bitrate']) && is_numeric($b['bitrate'])) {
			return $a['bitrate'] - $b['bitrate'];
		}
		return false;
	}

}
