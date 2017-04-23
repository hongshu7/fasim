<?php
namespace App\Model;
use Fasim\Core\Model;
//use App\Library\VersionCache;

class BaseModel extends Model {

	//protected $vcache;

	public function __construct() {
		parent::__construct();

		//$this->vcache =  new VersionCache();

	}

	
}

?>