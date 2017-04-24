<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
 
namespace Fasim\Facades;

use Fasim\Core\Application;
use Fasim\Core\Exception;

class Facade {
    public static function __callStatic($method, $args) {
        $instance = static::getFacadeRoot();

        switch (count($args))
        {
            case 0:
                return $instance->$method();

            case 1:
                return $instance->$method($args[0]);

            case 2:
                return $instance->$method($args[0], $args[1]);

            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);

            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);

            default:
                return call_user_func_array(array($instance, $method), $args);
        }
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot() {
        $accessor = static::getFacadeAccessor();
        $instance = Application::getInstance()->make($accessor);
        if ($instance == null) {
            throw new Exception('Facade get null for: '.$accessor);
        }
        return $instance;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new Exception('Facade does not implement getFacadeAccessor method.');
    }
}
