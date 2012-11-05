<?php

App::uses('AppModel', 'Model');

/**
 * Link Model
 * @property FeedItem $FeedItem
 * @property Mp3 $Mp3
 */
class Link extends AppModel {

	/**
	 * @var string
	 */
	public $displayField = 'url';

	/**
	 * @var array
	 */
	public $belongsTo = array('FeedItem' => array());

	/**
	 * @var array
	 */
	public $hasMany = array('Mp3' => array());

}
