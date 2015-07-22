<?php
namespace Rotor;

class Response {
    const REDIRECT_PERM = 301;
    const REDIRECT_FOUND = 302;
    const REDIRECT_SEE_OTHER = 303;
    const REDIRECT_TEMP = 307;

    const HEADER_CONTENT_TYPE = 'Content-type';
    const HEADER_CONTENT_LENGTH = 'Content-length';
    const HEADER_STATUS = 'Status';

    const STATUS_200 = '200 OK';
    const STATUS_404 = '404 Not Found';
    const STATUS_500 = '500 Internal Server Error';

    protected static $headers;
    protected static $status = '';

    protected static $autoHeaders = true;

    private static $initialized = false;
    public static function Init(){
        if (static::$initialized) {
            return;
        }
        self::$headers = array();
        self::$status = self::STATUS_200;
        self::$headers[static::HEADER_CONTENT_TYPE]=Mimes::getExtension('html');
        self::$autoHeaders = true;
        static::$initialized = true;
    }

    public static function setJSON(){
        static::Init();
        //Debug::log('Response::setJson()');
        self::$autoHeaders = false;
        self::setHeader(Response::HEADER_CONTENT_TYPE,Mimes::getType('json').'; charset=utf-8');
    }

    public static function setHeader($param,$value){
        static::Init();
        self::$autoHeaders = false;
        self::$headers[$param]=$value;
    }


    public static function outputHeaders(){
        static::Init();
        //Debug::group('Output Headers');
        foreach (self::$headers as $key=>$value){
            //Debug::log($key.': '.$value);
            header($key.': '.$value);
        }
        //Debug::groupClose();
    }

    public static function setStatus($status){
        static::Init();
        self::$autoHeaders = false;
        self::$status = $status;
    }

    public static function outputStatus(){
        static::Init();
        header($_SERVER['SERVER_PROTOCOL'].' '.self::$status);
    }

    public static function Output($data){
        static::Init();
        if (self::$autoHeaders) {
            if (is_object($data) || is_array($data)) {
                self::setJSON();
                $data = json_encode($data);
            }
        }
        self::outputStatus();
        self::outputHeaders();
        echo $data;
    }


    public static function Redirect($url,$code=self::REDIRECT_SEE_OTHER, $die=true){
        static::Init();
        if (Config::get('debug.redirect')){
            $data =  '<h1>Redirection Intercepted</h1>';
            $data.= '<p>Redirecting to <a href="'.$url.'">'.$url.'</a></p>';
            self::Output($data);
        } else {
            http_response_code($code);
            header('Location: '.$url);
        }
        if ($die == true){
            die();
        }
    }
}