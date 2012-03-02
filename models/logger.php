<?php
class Logger extends AppModel
{
	public $name = 'Logger';
	
	public $useTable = 'logs';
	
	public $displayField = 'id';
	
	public $actsAs = array('Containable');
	
	public $belongsTo = array(
		'LogDetail' => array('className' => 'Auditable.LogDetail'),
		'Responsible' => array(
			'className' => 'User',
			'foreignKey' => 'user_id'
			)
		);
	
	/**
	 * 
	 * 
	 * @param int $id
	 * @return array
	 */
	public function get($id, $loadResource = true)
	{
		$data = $this->find('first', array(
			'conditions' => array('Logger.id' => $id),
			'contain' => array('LogDetail', 'Responsible')
			)
		);
		
		$linked = null;
		
		if($loadResource)
		{
			$Resource = ClassRegistry::init($data[$this->name]['model_alias']);
			
			$linked = $Resource->find('first', array(
				'conditions' => array('id' => $data[$this->name]['model_id']),
				'recursive' => -1
				)
			);
		}
		
		if(!empty($linked))
		{
			$data[$Resource->name] = $linked[$Resource->name];
		}
		
		return $data;
	}

	/**
	 * Implementa função existis da API do Cake 2.0 na versão 1.3
	 * 
	 * Verifica se um registro existe no BD
	 * 
	 * @return bool
	 */
	public function exists()
	{
		if(empty($this->id))
			return false;

		return ($this->find('count', array('conditions' => array('Logger.id' => $this->id), 'recursive' => -1)) > 0);
	}
}