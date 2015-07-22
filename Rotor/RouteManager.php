<?php
namespace Rotor;

class RouteManager {

	protected $prefix = '';
	protected $routes = [];
    protected $namedRoutes = [];
    protected $current;

    protected $namesResolved = true;

	public function setPrefix($prefix){
		$this->prefix = $prefix;
	}

	public function add($route) {
        $this->namesResolved = false;
		$this->routes[] = $route;
	}

	public function remove($name) {
        $this->namesResolved = false;
		if (array_key_exists($name,$this->routes)){
			unset($this->routes[$name]);
		}
	}

	public function registerRoute($method,$pattern) {
        $this->namesResolved = false;
		$route = new Route();
		$route->method($method)->pattern($this->fixPrefix($pattern));
        $this->routes[] = $route;
		return $route;
	}

    public function findMatch($request, $setAsCurrent=false){
        _d($request,'find Match');
        _d($setAsCurrent,'setAsCurrent');
        foreach ($this->routes as $route){
            if ($route->matches($request)) {
                if ($setAsCurrent) {
                    $this->current=$route;
                }
                return $route;
            }
        }
        return false;
    }

    /**
     * @return Route
     */
    public function current(){
        return $this->current;
    }

    /**
     * @param $name
     * @return Route
     * @throws \Exception
     */
    public function find($name) {
        $this->resolveNames();
        if (array_key_exists($name,$this->namedRoutes)){
            return $this->namedRoutes[$name];
        }
        throw new \Exception('Route name "'.$name.'" not found');
    }

    private function resolveNames(){
        if ($this->namesResolved){
            return;
        }
        $this->namedRoutes = [];
        foreach ($this->routes as $route) {
            /* @var Route $route */
            $this->namedRoutes[$route->name()] = $route;
        }
        $namesResolved = true;
    }

	private function fixPrefix($pattern){
		$patternChar = substr($pattern,0,1);
		$prefixChar = substr($this->prefix,-1);
		if ($patternChar == '/' && $prefixChar == '/') {
			return str_replace('//','/',$this->prefix.$pattern);
		}
		if ($patternChar != '/' && $prefixChar != '/') {
			return $this->prefix.'/'.$pattern;
		};
		return $this->prefix.$pattern;
	}

    public function showAll(){
        $this->resolveNames();
        return $this->namedRoutes;
    }

    public function getPublic(){
        $this->resolveNames();
        $result = [];
        foreach ($this->namedRoutes as $name=>$route){
            $result[$name] = $route->getPublic();
        }
        return $result;
    }
}