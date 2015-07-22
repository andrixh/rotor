<?php
namespace Rotor;

class Request
{
    const GET = 'GET';
    const POST = 'POST';
    /**
     * @var RequestItem
     */
    protected static $current = null;

    protected static function createInstance(){
        if (static::$current !== null) {
            return;
        }
        static::$current = new RequestItem();
        static::$current->populateFromGlobals();
    }

    public function uri(){
        static::createInstance();
        return static::$current->uri();
    }

    public function chunks(){
        static::createInstance();
        return static::$current->uri();
    }

    public static function getCurrent(){
        static::createInstance();
        return static::$current;
    }

    public static function isPost(){
        static::createInstance();
        return static::$current->isPost();
    }

    public static function isGet(){
        static::createInstance();
        return static::$current->isGet();
    }
}
