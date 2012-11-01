<?php

App::uses('AppModel', 'Model');
App::import('Vendor', 'getid3/getid3');

/**
 * Mp3 Model
 *
 * @property Link $Link
 */
class Mp3 extends AppModel {

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'name';


	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Link' => array(
			'className' => 'Link',
			'foreignKey' => 'link_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

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
				}
			} else {
				CakeLog::write('error', 'Trying to get id3 info of file that does not exist, "' . $file . '"');
			}

			return $info;
		}
	}
}
