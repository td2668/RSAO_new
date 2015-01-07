<?php

/**
 * Replace array_fill_keys()
 *
 */
function php_compat_array_fill_keys($target, $value = '') {
    if(is_array($target)) {
        foreach($target as $key => $val) {
            $filledArray[$val] = is_array($value) ? $value[$key] : $value;
        }
    }
    return $filledArray;
}

// Define
if (!function_exists('array_fill_keys')) {
    function array_fill_keys($target, $value)
    {
        return php_compat_array_fill_keys($target, $value);
    }
}