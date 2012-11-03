<?php

App::uses('AppModel', 'Model');

/**
 * FeedItem Model
 *
 * @property Feed $Feed
 * @property Link $Link
 */
class FeedItem extends AppModel {

	/**
	 * Display field
	 * @var string
	 */
	public $displayField = 'title';

	/**
	 * belongsTo associations
	 * @var array
	 */
	public $belongsTo = array('Feed');

	/**
	 * hasMany associations
	 * @var array
	 */
	public $hasMany = array('Link');

	/* @var array */
	protected $blacklist;

	public function __construct() {
		parent::__construct();
		if($blacklist = Configure::read('FeedItem.blacklist')) {
			$this->blacklist = $blacklist;
		}
	}

	/**
	 * Check if a feed component is blacklisted
	 * @param $data
	 * @return bool
	 */
	public function isBlacklisted($data) {
		foreach($this->blacklist as $rule_name => $rule) {
			if($this->matches($data, $rule)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if feed data matches a blacklist rule
	 * @param array $data Feed data
	 * @param array $rule Blacklist rule data
	 * @return bool
	 */
	public function matches($data, $rule) {
		foreach($rule as $model_name => $fields) {
			if(empty($data[$model_name])) {
				return false;
			} else {
				foreach($fields as $field_name => $regex) {
					if(empty($data[$model_name][$field_name])) {
						return false;
					}
					if(!preg_match($regex, $data[$model_name][$field_name])) {
						return false;
					}
				}
			}
		}
		return true;
	}

}
