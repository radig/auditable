<?php
class Logger extends AppModel
{
	public $name = 'Logger';
	
	public $useTable = 'logs';
	
	public $displayField = 'id';
	
	public $actsAs = array('Containable');
	
	public $belongsTo = array('LogDetail');
	
	/**
	 * 
	 * 
	 * @param int $id
	 * @return array
	 */
	public function get($id)
	{
		$data = $this->find('first', array(
			'conditions' => array('Logger.id' => $id),
			'contain' => array('LogDetail')
			)
		);
		
		$Resource = ClassRegistry::init($data[$this->name]['model_alias']);
		
		$linked = $Resource->find('first', array(
			'conditions' => array('id' => $data[$this->name]['model_id']),
			'recursive' => -1
			)
		);
		
		if(!empty($linked))
		{
			$data[$Resource->name] = $linked[$Resource->name];
		}
		
		return $data;
	}
}