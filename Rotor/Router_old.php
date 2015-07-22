<?php
namespace Rotor\;

use Rotor\Route;


class Router_old
{
    const EVENT_ROUTE_NOT_FOUND = 'ROUTE_NOT_FOUND';

    protected static $routesPath = '';
    protected static $availableRoutes = [];
    private static $latestChange = 0;

    /**
     * @var Route
     */
    protected static $currentRoute;

    public static function __Init()
    {
        $routesPath = Config::get('path.routes');
        self::$routesPath = $routesPath;
        self::loadRoutes($routesPath);
        /*$cacheCollection = 'routes.'.Environment::get();
        if (Environment::isProd()){
            if (DiskCache::exists($cacheCollection)){
                $cachedData = DiskCache::get($cacheCollection);
                self::$availableRoutes = $cachedData;
            } else {
                self::loadRoutes($routesPath);
                DiskCache::set($cacheCollection,self::$availableRoutes);
            }
        }

        if (Environment::isDev()){
            self::loadRoutes($routesPath);
            if (DiskCache::time($cacheCollection) < self::$latestChange){
                DiskCache::set($cacheCollection, self::$availableRoutes);
                DiskCache::clear('routes.'.Environment::PROD);
            }
        }*/
        //Debug::log(self::$availableRoutes,'Available Routes');
        //Debug::groupClose();
    }

    protected static function loadRoutes($path, $prefix = '')
    {
        //Debug::groupCollapsed('Router::loadRoutes("'.$path.'" ,"'.$prefix.'")');
        $routesPath = $path;
        if ($routesPath != self::$routesPath) {
            $routesPath = Finder::path(Finder::PATH_ROOT). $path;
        }
        //Debug::log($routesPath . '*.route.php');
        $routeFiles = glob($routesPath . '*.route.php');
        //Debug::log($routeFiles);
        foreach ($routeFiles as $routeFile) {
            self::loadFile($routeFile, $prefix);
        }
        $routeEnvFiles = glob($routesPath . '*.route.' . Environment::get() . '.php');
        //Debug::log($routeEnvFiles);
        foreach ($routeEnvFiles as $routeFile) {
            self::loadFile($routeFile, $prefix);
        }

        //Debug::groupClose();
    }

    protected static function loadFile($file, $prefix)
    {
        //Debug::groupCollapsed('Router::loadFile('.$file.' ,'.$prefix.')');
        $fileTime = filemtime($file);
        if ($fileTime > self::$latestChange) {
            self::$latestChange = $fileTime;
        }
        $fileRoutes = include($file);
        if (is_array($fileRoutes)) {
            self::addRoutes($fileRoutes, $prefix);
        }
        //Debug::groupClose();
    }

    protected static function addRoutes($routeDefs, $prefix)
    {
        //Debug::groupCollapsed('Router::addRoutes()');
        //Debug::log(func_get_args(),'args');
        $namePrefix = '';
        $namespace = '';
        foreach ($routeDefs as $routeName => $routeDef) {
            //Debug::log($routeDef,$routeName);
            if ($routeName == '_name_prefix') {
                $namePrefix = $routeDef;
            } else if ($routeName == '_namespace') {
                $namespace = $routeDef;
            }else if (isset($routeDef['include'])) {
                self::loadRoutes($routeDef['include'], $routeDef['prefix']);
            } else {
                if (substr($routeDef['controller'],0,1) != '@'){
                    $routeDef['controller']='@'.$namespace.':'.$routeDef['controller'];
                }
                //Debug::groupCollapsed('making new route with:');
                //Debug::log($routeName,'name');
                //Debug::log($routeDef,'def');
                //Debug::log($prefix,'prefix');
                //Debug::log($namePrefix,'name prefix');
                $newRoute = new Route($namePrefix.$routeName,$routeDef,$prefix);
                if ($newRoute->isValid()) {
                    self::$availableRoutes[$namePrefix.$routeName] = $newRoute;
                }

                //Debug::groupClose();
            }
        }
        //Debug::groupClose();
    }

    public static function match($request){ //ok
        //Debug::group('Router::match()');
        $result = false;
        foreach (self::$availableRoutes as $route){
            if ($route->matches($request)){
                $result = $route;
                self::$currentRoute = $result;
                break;
            }
        }
        //Debug::log(self::$currentRoute,'current route');
        if (!$result){
            Events::fire(self::EVENT_ROUTE_NOT_FOUND);
        }
        //Debug::groupClose();
        return $result;
    }

    public static function current(){ //ok
        return self::$currentRoute;
    }

    public static function path($routeName, $params=[], $absolute=false){ //return an uri
        if (array_key_exists($routeName, self::$availableRoutes)){
            $route = self::$availableRoutes[$routeName];
            $result = $route->makePath($params);
            if ($absolute){
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $result = $protocol.$_SERVER['SERVER_NAME'].$result;
            }
            return $result;
        } else {
            trigger_error('Route::path() error: No matching routes for "'.$routeName.'"',E_USER_WARNING);
            return '';
        }

    }


    /**
     * @param $routeName
     * @return \Turbina\Core\Route
     */
    public static function getRoute($routeName) {
        if (isset(self::$availableRoutes[$routeName])){
            return self::$availableRoutes[$routeName];
        } else {
            return false;
        }
    }
}