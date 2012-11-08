<?php

App::uses('AppModel', 'Model');
App::uses('HarvestHttpSocket', 'Lib');
App::import('Vendor', 'getid3/getid3');

/**
 * Before starting a download check that a file with the same name does not already exists
 * After downloading a file use url, request, and data to determine if a copy of the file already exists
 * 	If a copy does exist use file conflict resolver to determine which one to keep
 * 	After resolving conflict name remaining file appropriately and remove unused data
 */

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
		$this->downloadPath = WWW_ROOT . 'downloads' . DS;
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
	 * @param array $mp3
	 */
	public function download($mp3) {
		CakeLog::write('scrape', 'Downloading ' . $mp3['Mp3']['url']);
		$downloader = new FileDownloader();
		//Check if file exists
		if(!$this->downloadExists($mp3['Mp3']['filename'])) {
			try {
				//Download mp3
				$path = $downloader->save($mp3['Mp3']['url'], $this->downloadPath . $mp3['Mp3']['filename']);
				//Update mp3 record
				$mp3 = array_merge($mp3['Mp3'], $this->getDataFromFile($path)['Mp3']);
				$mp3['Mp3']['downloaded'] = date('Y-m-d H:i:s');
				if($this->save($mp3)) {
					//Remove any duplicate copies
					$this->resolveMp3Conflicts($mp3, $path);
				} else {
					CakeLog::write('scrape', 'Unable to save Mp3. Data: ' . json_encode($mp3));
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
	protected function downloadExists($filename) {
		return $filename ? file_exists(APP_PATH . 'downloads/' . $filename) : false;
	}

	/**
	 * Get mp3 data from file
	 * @param $path
	 * @return array
	 */
	public function getDataFromFile($path) {
		$data = file_get_contents($path);
		$mp3['Mp3'] = array(
			'filename' => basename($path),
			'hash' => md5($data),
			'size' => strlen($data)
		);
		return array_merge($mp3['Mp3'], $this->getId3Data($path));
	}

	public function resolveMp3Conflicts($mp3) {
		//Check for copies, remove inferior, flag MP3 records as removed
		//Check that file has not already been downloaded
		if(!$this->hashExists($mp3['Mp3']['hash'])) {
			$copy = $this->findCopy($mp3);
			if($copy) {
				if($mp3['Mp3']['bitrate'] > $copy['Mp3']['bitrate']) {
					//Remove inferior quality copy
					unlink($this->downloadPath . $copy['Mp3']['filename']);
					//Note removal reason
					$copy['Mp3']['error'] = 'Superior quality copy found';
					$this->save($copy, false, array('error'));
				} else {
					//Remote inferior quality copy from temp location
					unlink($tmp_name);
					$mp3['Mp3']['error'] = 'Inferior quality copy';
				}
			} else {
				//TODO: Move mp3 to new location
			}
		} else {
			CakeLog::write('scrape', 'Found duplicate mp3 by hash ' . $mp3['Mp3']['hash'] . ' for ' . $mp3['Mp3']['id']);
		}
	}

	/**
	 * Get Mp3 model data from id3 tags of an mp3 file
	 * @param string $file
	 * @return array
	 */
	public function getId3Data($file) {
		//Defaults
		$mp3 = array(
			'playtime_seconds' => null,
			'bitrate' => null,
			'artist' => null,
			'name' => null,
			'album' => null
		);
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
			if(isset($id3['playtime_seconds'])) $mp3['length'] = $id3['playtime_seconds'];
			//Bitrate
			if(isset($id3['audio']['bitrate'])) $mp3['bitrate'] = $id3['audio']['bitrate'];

			//id3 tag data
			if(!empty($data)) {
				if(isset($data['artist'])) $mp3['artist'] = current($data['artist']);
				if(isset($data['title'])) $mp3['name'] = current($data['title']);
				if(isset($data['album'])) $mp3['album'] = current($data['album']);
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
			CakeLog::write('error', 'Trying to get id3 info of file "' . $file . '" that does not exist.');
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
	 * Finds a copy of a song
	 * @param array $mp3
	 * @return array
	 */
	public function findCopy($mp3) {
		return $this->find('first', array(
			'conditions' => array(
				'Mp3.artist' => $mp3['Mp3']['artist'],
				'Mp3.name' => $mp3['Mp3']['name']
			),
			'recursive' => -1
		));
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
