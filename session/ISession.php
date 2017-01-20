<?php
namespace Fasim\Session;

interface ISession {
	public function __construct($prefix);

	public function set($name, $value = '');

	public function get($name);

	public function delete($name);

	public function clear();

}
?>
