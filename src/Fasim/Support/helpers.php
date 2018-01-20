<?php
$env_vars = null;
function env($key, $default, $type='string') {
    global $env_vars;
    if ($env_vars === null) {
        $env_file = APP_PATH . '.env';
        if (file_exists($env_file)) {
            $env_vars = parse_ini_file($env_file);
        }
    }
    if (isset($env_vars[$key])) {
        $val = $env_vars[$key];
        if ($type == 'int' || $type == 'integer') {
            $val = intval($val);
        } else if ($type == 'float' || $type == 'double') {
            $val = floatval($val);
        } else if ($type == 'bool' || $type == 'boolean') {
            $val = str_to_bool($val.'');
        } else {
            $val = $val.'';
        }
        return $val;
    }
    return $default;
}

function str_to_bool($var) {
    if (!is_string($var)) return (bool) $var;
    switch (strtolower($var)) {
        case '1':
        case 'true':
        case 'on':
        case 'yes':
        case 'y':
            return true;
        default:
            return false;
    }
}