<?php
namespace Fasim\Event;

class ExceptionEvent extends Event {
	
	private $exception;
	
	public function __construct($type, $exception) {
		$this->type = $type;
		$this->exception = $exception;
	}
	
	public function getException() {
		return $this->exception;
	}

	public function getMessage() {
		return $this->exception->getMessage();
	}

	public function getPrevious() {
		return $this->exception->getPrevious();
	}
	
	public function getCode() {
		return $this->exception->getCode();
	}
	
	public function getFile() {
		return $this->exception->getFile();
	}
	
	public function getLine() {
		return $this->exception->getLine();
	}
	
	public function getTrace() {
		return $this->exception->getTrace();
	}
	
	public function getTraceAsString() {
		return $this->exception->getTraceAsString();
	}
	
	public function __toString() {
		return $this->exception->__toString();
	}
	
	private function __clone() {
		return clone $this->exception;
	}
	

}

?>