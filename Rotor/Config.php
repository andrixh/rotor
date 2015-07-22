<?php
namespace Rotor;

use Rotor\Config\ConfigManager;

class Config {

    /**
     * @var ConfigManager
     */
    protected static $instance = null;

    protected static function Init(){
        if (static::$instance === null) {
            static::$instance = new ConfigManager(Path::Create($_SERVER['DOCUMENT_ROOT'])->append('../config/'), Environment::get());
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        static::Init();
        return static::$instance->has($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get($key) {
        static::Init();
        return static::$instance->get($key);
    }

    public static function getAllPublic(){
        static::Init();
        return static::$instance->getAllPublic();
    }

    public static function getAll(){
        static::Init();
        return static::$instance->getAll();
    }
}