<?php

use ArmCyber\Localization\Services\Facades\Localization;

if (!function_exists('_i')) {
    function _i($message, $args = null)
    {
        return Localization::translate($message, $args);
    }
}

if (!function_exists('_')) {
    function _($message, $args = null)
    {
        return _i($message, $args);
    }
}

if (!function_exists('__')) {
    function __($message, $args = null)
    {
        return _i($message, $args);
    }
}

if (!function_exists('_t')) {
    function _t($message, $args = null)
    {
        return _i($message, $args);
    }
}

if (!function_exists('_n')) {
    function _n($singular, $plural, $count, $args = null)
    {
        return Localization::translatePlural($singular, $plural, $count, $args);
    }
}

if (!function_exists('pgettext')) {
    function pgettext($context, $msgid)
    {
        $contextString = "{$context}\004{$msgid}";
        $translation = _t($contextString);

        if ($translation == $contextString) {
            return $msgid;
        } else {
            return $translation;
        }
    }
}
