<?php
namespace Fasim\Event;

class Event {
	
	public static $EXCEPTION = 'exception';

	public static $APP_START = 'app_start';
	public static $APP_FINISH = 'app_finish';

	public static $APP_READY = 'app_ready';
				    
	public static $CONTROLLER_START = 'controller_start';
	public static $CONTROLLER_FINISH = 'controller_finish';

	public static $ACTION_START = 'action_start';
	public static $ACTION_FINISH = 'action_finish';

	protected $type = '';

	public function __construct($type) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}

}

?>