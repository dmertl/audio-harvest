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


	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'FeedItem' => array(
			'className' => 'FeedItem',
			'foreignKey' => 'feed_item_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Mp3' => array(
			'className' => 'Mp3',
			'foreignKey' => 'link_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

	const TYPE_MP3 = 1;
	const TYPE_MEDIAFIRE = 2;
	const TYPE_ZSHARE = 3;
	const TYPE_SOUNDCLOUD = 4;

}
