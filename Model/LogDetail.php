<?php
class LogDetail extends AppModel
{
	public $name = 'LogDetail';

	public $useTable = 'log_details';

	public $hasOne = array('Auditable.Logger');
}