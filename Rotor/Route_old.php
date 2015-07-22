<?php
namespace Rotor;


class Route_old
{
	/**
	 * @var $name string Name of the route
	 */
    protected $name;

	/**
	 * @var string declared pattern (partial regex) placeholders with { }
	 */
    protected $pattern = '';

    protected $bundle = '';
    protected $controller = '';
    protected $action = '';

    protected $requirements = [];
    protected $parameters = [];
    protected $expression = '';
    protected $method = '';

    protected $valid = true;

    public function __construct($routeName,$routeDef,$prefix=''){
        $this->name = $routeName;
        if (isset($routeDef['pattern'])) {
            $this->setPattern($prefix . $routeDef['pattern']);
        }
        if (isset ($routeDef['controller'])) {
            $controllerParts = explode(':',$routeDef['controller']);
            //Debug::log($controllerParts,'controllerParts');
            if (count($controllerParts) == 3){
                $this->setBundle(str_replace('@','',$controllerParts[0]));
                $this->setController($controllerParts[1]);
                $this->setAction($controllerParts[2]);
            } else {
                $this->setController($controllerParts[0]);
                $this->setAction($controllerParts[1]);
            }
        }
        if (isset ($routeDef['method'])){
            $this->method = explode(',',strtolower($routeDef['method']));
        }
        if (isset ($routeDef['requirements'])) {
            $this->setRequirements($routeDef['requirements']);
        }
        $this->prepare();
    }


    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        $params = [];
        $matchCount = preg_match_all("/{([^{}]*)}/",$this->pattern, $params);
        if ($matchCount === false){
            trigger_error('Malformed Route Pattern in route '.$this->name.'.',E_USER_ERROR);
            $this->valid = false;
        }
        if ($matchCount>0){
            for ($i = 0; $i<$matchCount; $i++){
                $this->parameters[$params[0][$i]] = $params[1][$i];
            }
        }
    }

    public function getPattern()
    {
        return $this->pattern;
    }


    public function getRequirements()
    {
        return $this->requirements;
    }

    public function setRequirements($requirements)
    {
        $this->requirements = $requirements;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getBundle(){
        return $this->bundle;
    }

    public function setBundle($bundle){
        $this->bundle = $bundle;
    }

    public function getName(){
        return $this->name;
    }

    public function prepare(){
        $this->expression = $this->calculateExpression();
    }

    public function isValid() //TODO: set validation criteria?
    {
        return $this->valid;
    }

    public function getExpression(){
        return $this->expression;
    }

    public function getParams(){
        $param_map = array_flip($this->parameters);
        $regex = $this->expression;

        $uri = Request::uri();

        $matches = preg_split($regex, $uri, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

        $params = [];
        foreach ($param_map as $key=>$value) {
            $params[$key] = array_shift($matches);
        }
        return $params;
    }

    private function calculateExpression(){ //prepares regular expression to match against route;
        $defaultRegex = '([^/{}]+)';
        $pattern = $this->pattern;
        foreach ($this->parameters as $find=>$replace){
            if (array_key_exists($replace,$this->requirements)){
                $pattern = str_replace($find,'('.$this->requirements[$replace].')',$pattern);
            } else {
                $pattern = str_replace($find,$defaultRegex,$pattern);
            }
        }
        return "#^".$pattern."/?$#";
    }

    public function matches($request){
        if (preg_match($this->expression,$request)){
            $result = true;
            if ($this->method) {
                if (!in_array(strtolower(Request::method()),$this->method)){
                    $result = false;
                }
            }
        } else {
            $result = false;
        }
        return $result;
    }

    public function makePath($params){
        //Debug::group('makePath');
        //Debug::log($params);
        $result = $this->pattern;
        if (is_object($params)){
            $params = json_decode(json_encode($params),true);
        }

        foreach ($params as $key=>$value) {
            $result = str_replace('{'.$key.'}',$value,$result);
        }
        //Debug::log($result,'result');
        //Debug::groupClose();
        return $result;
    }
}