<?php
namespace Rotor;

class Environment {
    const DEV = 'DEV';
    const PROD = 'PROD';

    protected static $initialized = false;
    protected static $current = '';

    protected static function Init(){
        if (static::$initialized) {
            return;
        }
        if (preg_match("#local$#",$_SERVER['SERVER_NAME'])) {
            static::$current = static::DEV;
        } else {
            static::$current = static::PROD;
        }
        static::$initialized = true;
    }

    public static function get(){
        static::Init();
        return static::$current;
    }

    public static function is_dev(){
        static::Init();
        return static::$current == static::DEV;
    }

    public static function is_prod(){
        static::Init();
        return static::$current == static::PROD;
    }
}