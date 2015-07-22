<?php
namespace Rotor;

/**
 * Class Route
 * @package Rotor
 *
 * Temporary implementation - for the transition. Allows to map nice urls to plain includes, with mapping to $_GET and $_POST
 */
class Route
{
    const DESTINATION_INCLUDE = 'DESTINATION_INCLUDE';
    const DESTINATION_CLOSURE = 'DESTINATION_CLOSURE';
    const DESTINATION_CALL = 'DESTINATION_CALL';

    /**
     * @var $name string Name of the route
     */
    protected $name;

    /**
     * @var string declared pattern (partial regex) placeholders with { }
     */
    protected $pattern = '';

    protected $constraints = [];
    protected $parameters = [];
    protected $expression = '';
    protected $method = '*';

    protected $mapGet = [];
    protected $mapPost = [];

    protected $valuesGet = [];
    protected $valuesPost = [];

    protected $destinationType;
    protected $file;
    protected $closure; //TODO
    protected $call;




    /**
     * Set Request Method
     *
     * @param null $method
     * @return $this|string
     */
    public function method($method=null){
        if ($method === null){
            return $this->method;
        }
        $this->method = $method;
        return $this;
    }

    public function getMethod(){
        return $this->method;
    }


    /**
     * Set Pattern
     *
     * @param null $pattern
     * @return $this|string
     */
    public function pattern($pattern=null)
    {
        if ($pattern === null) {
            return $this->pattern;
        }
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
        return $this;
    }


    /**
     * Name this route
     *
     * @param null $name
     * @return $this|string
     */
    public function name($name=null) {
        if ($name === null) {
            return $this->name;
        }
        $this->name = $name;
        return $this;
    }


    /**
     * Add a single Constraint
     *
     * @param $paramName|array
     * @param $constraint|null
     * @return $this
     */
    public function constrain($paramName,$constraint=null) {
        if (is_array($paramName)){
            $array = $paramName;
            foreach ($array as $paramName=>$constraint) {
                $this->constraints[$paramName] = $constraint;
            }
        } else {
            $this->constraints[$paramName] = $constraint;
        }
        return $this;
    }


    public function constraints($constraints = null)
    {
        if ($constraints == null) {
            return $this->constraints;
        }
        $this->constraints = $constraints;
        return $this;
    }

    public function setRequirements($constraints)
    {
        $this->constraints = $constraints;
    }

    public function matches(RequestItem $request){
        $this->prepare();
        //echo $request->method().' -- '.$this->method.' -- '.$request->uri().' --- '.$this->expression.'<br/>';
        if ($this->method == '*' || $request->method()==$this->method){
            //echo 'method ok</br>';
            if (preg_match($this->expression,$request->uri())){
                return true;
            }
        }
        return false;
    }

    public function makePath($params=[]){
        $result = $this->pattern;
        if (is_object($params)){
            $params = json_decode(json_encode($params),true);
        }

        foreach ($params as $key=>$value) {
            $result = str_replace('{'.$key.'}',$value,$result);
        }
        return $result;
    }

    public function mapGet($key,$param=null){
        if (is_array($key)) {
            $array = $key;
            foreach ($array as $key=>$param) {
                $this->mapGet[$key] = $param;
            }
        } else {
            $this->mapGet[$key] = $param;
        }
        return $this;
    }

    public function mapPost($key, $param) {
        if (is_array($key)) {
            $array = $key;
            foreach ($array as $key=>$param) {
                $this->mapPost[$key] = $param;
            }
        } else {
            $this->mapPost[$key] = $param;
        }
        return $this;
    }

    public function valueGet($key,$value=null){
        if (is_array($key)) {
            $array = $key;
            foreach ($array as $key=>$value) {
                $this->valuesGet[$key] = $value;
            }
        } else {
            $this->valuesGet[$key] = $value;
        }
        return $this;
    }

    public function valuePost($key,$value=null){
        if (is_array($key)) {
            $array = $key;
            foreach ($array as $key=>$value) {
                $this->valuesPost[$key] = $value;
            }
        } else {
            $this->valuesPost[$key] = $value;
        }
        return $this;
    }


    public function file($filename){
        $this->destinationType = static::DESTINATION_INCLUDE;
        $this->file = $filename;
        return $this;
    }

    public function call($controllerAction){
        $this->destinationType = static::DESTINATION_CALL;
        $this->call = $controllerAction;
        return $this;
    }

    public function go(RequestItem $request){
        $params = $this->getParams($request);
        $result = [
            'type' => $this->destinationType
        ];
        switch ($this->destinationType) {
            case static::DESTINATION_INCLUDE:
                foreach ($this->mapPost as $key=>$value) {
                    $_POST[$value] = $params[$key];
                }
                foreach ($this->mapGet as $key=>$value) {
                    $_GET[$value] = $params[$key];
                }
                foreach ($this->valuesGet as $key=>$value) {
                    $_GET[$key] = $value;
                }
                foreach ($this->valuesPost as $key=>$value) {
                    $_POST[$key] = $value;
                }
                $result['file']=$this->file;
            break;

            case static::DESTINATION_CALL:
                $result['controllerAction'] = $this->call;
            break;
        }
        return $result;
    }

    protected function prepare(){
        $this->expression = $this->calculateExpression();
    }

    public function getExpression(){
        return $this->expression;
    }

    public function getParams(RequestItem $request){
        $param_map = array_flip($this->parameters);
        $regex = $this->expression;
        $matches = preg_split($regex, $request->uri(), null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $params = [];
        foreach ($param_map as $key=>$value) {
            $params[$key] = array_shift($matches);
        }
        return $params;
    }

    /**
     * prepares regular expression to match against route;
     * @return string
     */
    private function calculateExpression(){
        $defaultRegex = '([^/{}]+)';
        $pattern = $this->pattern;
        foreach ($this->parameters as $find=>$replace){
            if (array_key_exists($replace,$this->constraints)){
                $pattern = str_replace($find,'('.$this->constraints[$replace].')',$pattern);
            } else {
                $pattern = str_replace($find,$defaultRegex,$pattern);
            }
        }
        return str_replace('//','/',"#^".$pattern."/?$#");
    }

    public function getPublic(){
        $result = [
            'pattern'=>$this->pattern,
            'name'=>$this->name
        ];
        return $result;
    }

}