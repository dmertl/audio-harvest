<?php

App::uses('AppModel', 'Model');

/**
 * Link Model
 *
 * @property FeedItem $FeedItem
 * @property Mp3 $Mp3
 */
class Link extends AppModel {

	/**
	 * Display field
	 *
	 * @var string
	 */
	public $displayField = 'url';

	/**
	 * belongsTo associations
	 *
	 * @var array
	 */
	public $belongsTo = array('FeedItem' => array());

	/**
	 * hasMany associations
	 *
	 * @var array
	 */
	public $hasMany = array('Mp3' => array());

}
