<?php
namespace Rotor;

class RequestItem
{
    const GET = 'GET';
    const POST = 'POST';

    protected  $uri;
    protected  $chunks;
    protected  $get = [];
    protected  $post = [];
    protected  $method = '';
    protected  $protocol = '';

    protected  $requestFiles = array();

    public  function __construct()
    {

    }

    public function populateFromGlobals(){
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('?', $uri);
        $this->setUri($parts[0]);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->get = $_GET;
        $this->post = $_POST;
        $this->protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])?'https':'http';
        $this->prepareFiles();
    }

    protected  function prepareFiles(){
        foreach ($_FILES as $name=>$files){
            if (is_array($files['name'])){
                $this->requestFiles[$name] = array();
                for ($i = 0; $i<count($files['name']); $i++){
                    $newFile = new RequestFile($files['name'][$i],$files['type'][$i],$files['tmp_name'][$i],$files['error'][$i],$files['size'][$i]);
                    $this->requestFiles[$name][] = $newFile;
                }
            } else {
                $newFile = new RequestFile($files['name'],$files['type'],$files['tmp_name'],$files['error'],$files['size']);
                $this->requestFiles[$name] = $newFile;
            }
        }
    }


    /**
     * @param $i
     * @param null $j
     * @return RequestFile[]
     */
    public  function file($i,$j=null){
        $result = null;
        if (is_null($j)){
            $result = (isset($this->requestFiles[$i])?$this->requestFiles[$i]:false);
        } else {
            $result = (isset($this->requestFiles[$i][$j])?$this->requestFiles[$i][$j]:false);
        }
        return $result;
    }

    public  function files(){
        return $this->requestFiles;
    }

    public function setUri($uri){
        $this->uri = $uri;
        $chunks = explode('/',$uri);

        if (count($chunks)>0 && $chunks[0] == '') {
            array_shift($chunks);
        }
        if (count($chunks)>0 && $chunks[count($chunks)-1] == '') {
            array_pop($chunks);
        }
        $this->chunks = $chunks;
    }

    public function chunk($index) {
        if (isset($this->chunks[$index])){
            return $this->chunks[$index];
        }
        return '';
    }

    public function chunks(){
        return $this->chunks;
    }

    public  function uri()
    {
        return $this->uri;
    }

    public  function get($key)
    {
        if (isset($_GET[$key])){
            return $_GET[$key];
        } else {
            return null;
        }
    }

    public function setPost($post){
        $this->post = $post;
    }

    public function setGet($get){
        $this->get = $get;
    }

    public  function post($key=null) {
        if (func_num_args() == 1){
            return $_POST[$key];
        } else {
            return $_POST;
        }
    }

    public  function hasPost($key) {
        return isset($_POST[$key]);
    }

    public  function method(){
        return $this->method;
    }

    public  function isGet(){
        return $this->method == static::GET;
    }

    public  function isPost(){
        return $this->method == static::POST;
    }

}
