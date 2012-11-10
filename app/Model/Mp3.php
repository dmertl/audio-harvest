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
	 * @var FileDownloader
	 */
	protected $downloader;

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->downloadPath = WWW_ROOT . 'downloads' . DS;
		$this->downloader = new FileDownloader();
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
		//Check if file exists
		if(!$this->downloadExists($mp3['Mp3']['filename'])) {
			try {
				//Download mp3
				$path = $this->downloader->save($mp3['Mp3']['url'], $this->downloadPath . $mp3['Mp3']['filename']);
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
		return $filename ? file_exists($this->downloadPath . $filename) : false;
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
		return array_merge($mp3['Mp3'], $this->getId3DataFromFile($path));
	}

	/**
	 * Resolve conflicts by removing lower quality copies of duplicate mp3s
	 * @param array $new
	 */
	public function resolveMp3Conflicts($new) {
		//Attempt to find copy
		$copy = $this->findCopy($new);
		if($copy) {
			//Remove inferior bitrate copy
			if($new['Mp3']['bitrate'] > $copy['Mp3']['bitrate']) {
				$remove = $copy;
				$remove['Mp3']['error'] = 'Superior quality copy found';
			} else if($new['Mp3']['bitrate'] < $copy['Mp3']['bitrate']) {
				$remove = $new;
				$remove['Mp3']['error'] = 'Superior quality copy exists';
			} else {
				$remove = $new;
				$remove['Mp3']['error'] = 'Duplicate of ' . $copy['Mp3']['id'];
			}
			//Remove inferior quality copy
			unlink($this->downloadPath . $remove['Mp3']['filename']);
			//Save error message
			$this->save($remove, false, array('error'));
		}
	}

	/**
	 * Get Mp3 model data from id3 tags of an mp3 file
	 * @param string $file
	 * @return array
	 */
	public function getId3DataFromFile($file) {
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
				'or' => array(
					array(
						'Mp3.hash' => $mp3['Mp3']['hash']
					),
					array(
						'Mp3.artist' => $mp3['Mp3']['artist'],
						'Mp3.name' => $mp3['Mp3']['name']
					)
				)
			),
			'recursive' => -1
		));
	}

}
