<?php
namespace Rotor;

/**
 * Class Router
 * Static wrapper for RouteManager
 * @package Rotor
 */
class Router {


    public static function setPrefix($prefix){
        static::getInstance()->setPrefix($prefix);
    }

    /**
     * @var RouteManager
     */
    protected static $instance = null;

    /**
     * @return RouteManager
     */
    protected static function getInstance(){
        if (static::$instance === null) {
            static::$instance = new RouteManager();
        }
        return static::$instance;
    }

    public static function get($pattern){
        return static::getInstance()->registerRoute(Request::GET,$pattern);
    }

    public static function post($pattern){
        return static::getInstance()->registerRoute(Request::POST,$pattern);
    }

    public static function any($pattern){
        return static::getInstance()->registerRoute('*',$pattern);
    }

    public static function current(){
        return static::getInstance()->current();
    }

    public static function findMatch($uri,$setAsCurrent = false){
        return static::getInstance()->findMatch($uri,$setAsCurrent);
    }

    public static function path($name,$params=[]){
        return static::$instance->find($name)->makePath($params);
    }

    public static function showAll(){
        return static::$instance->showAll();
    }

    public static function getPublic(){
        return static::$instance->getPublic();
    }
}