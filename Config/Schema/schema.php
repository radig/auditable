<?php
class auditableSchema extends CakeSchema
{
	public $name = 'auditable';

	public function before($event = array())
	{
		return true;
	}

	public function after($event = array())
	{
	}

	public $logs = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
		'responsible_id' => array('type' => 'integer', 'null' => false, 'default' => null),
		'model_alias' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50),
		'model_id' => array('type' => 'integer', 'null' => false, 'default' => null),
		'type' => array('type' => 'integer', 'null' => false, 'default' => 0),
		'log_detail_id' => array('type' => 'integer', 'null' => false, 'default' => null),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'types' => array('column' => 'type', 'unique' => 0))
	);

	public $log_details = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 11, 'key' => 'primary'),
		'difference' => array('type' => 'text', 'null' => false, 'default' => null),
		'statement' => array('type' => 'text', 'null' => true, 'default' => null),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);
}