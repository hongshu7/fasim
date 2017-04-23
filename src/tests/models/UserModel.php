<?php
namespace App\Model;
class UserModel extends BaseModel {

	protected $tableName = 'user';
	protected $primaryKey = '_id';
	protected $schema = [
		'_id'			=> ['type' => 'objectid', 'default' => 'auto'],
		'nickname' 		=> ['type' => 'string', 'default' => ''],
		'avatar'		=> ['type' => 'string', 'default' => ''], 
		'gender'		=> ['type' => 'int', 'default' => 1], 
		'createdAt' 	=> ['type' => 'timestamp', 'default' => '$now'],
		'status' 		=> ['type' => 'int', 'default' => 1]
	];



}

?>