<?php
namespace Fasim\Facades;


/**
 * This file copy form codeigniter
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
class Security extends Facade {
	/**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'security';
    }
}
