<?php
$env_vars = null;
function env($key, $default) {
    global $env_vars;
    if ($env_vars === null) {
        $env_file = APP_PATH . '.env';
        if (file_exists($env_file)) {
            $env_vars = parse_ini_file($env_file);
        }
    }
    if (isset($env_vars[$key])) {
        return $env_vars[$key];
    }
    return $default;
}