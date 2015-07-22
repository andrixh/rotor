<?php
namespace Rotor\Assets;

use Rotor\Environment;
//use Rotor\Config;
use lessc;
use scssc;
use Minify_CSS_Compressor;
use JSMin;

class Asset
{
    const SCRIPT = 'js';
    const STYLE = 'css';

    protected $config;

    protected $name;
    protected $type;
    protected $inline = false;
    protected $external = false;
    protected $combine = true;
    protected $minify = true;
    protected $dynamic=false;
    protected $place_in_head = false; //only meaningful for scripts

    protected $data = '';

    protected $sourcePath = '';
    protected $sourceTime = 0;

    public function __construct($assetDef, AssetsConfig $config)
    {
        $this->config = $config;
        $defParts = explode(' ', $assetDef);
        $this->name = $defParts[0];

        if (preg_match("#.js(\?.*)?$#", $this->name)) {
            $this->type = self::SCRIPT;
        } else if (preg_match("#.css(\?.*)?$#", $this->name)) {
            $this->type = self::STYLE;
        }

        if (preg_match("#^//#", $this->name)) {
            $this->inline = false;
            $this->external = true;
            $this->minify = false;
            $this->combine = false;
        } else if (count($defParts) > 1) {
            $flags = $defParts[1];

            if (stristr($flags, 'i') !== false) {
                $this->inline = true;
            }
            if (stristr($flags, 'x') !== false) {
                $this->external = true;
            }
            if (stristr($flags, 's') !== false) {
                $this->combine = false;
            }
            if (stristr($flags, 'm') !== false) {
                $this->minify = false;
            }
            if (stristr($flags, 'd') !== false) {
                $this->dynamic = true;
            }
            if (stristr($flags, 'h') !== false) {
                $this->place_in_head = true;
            }
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType(){
        return $this->type;
    }

    public function isInline()
    {
        return $this->inline;
    }

    public function isExternal()
    {
        return $this->external;
    }

    public function canCombine()
    {
        return $this->combine && !$this->dynamic;
    }

    public function canMinify()
    {
        return $this->minify && !$this->dynamic;
    }

    public function inHead(){
        return $this->place_in_head;
    }

    public function isDynamic(){
        return $this->dynamic;
    }

    public function getSourceTime(){
        return $this->sourceTime;
    }

    public function getData(){
        return $this->data;
    }

    public function findSource(){
        if ($this->external){
            $this->sourcePath = $this->name;
            _u();
            return;
        }

        $envPrefix = '.'.Environment::get();
        if ($this->type == self::SCRIPT) {
            $possibleExtensions = [$envPrefix.'.js','.js'];
            $extfind = "#\\.js$#";
        } else if ($this->type == self::STYLE) {
            $possibleExtensions = [$envPrefix.'.css','.css',$envPrefix.'.less','.less',$envPrefix.'.scss','.scss'];
            $extfind = '#\\.css$#';
        }

        $baseName = $this->config->sourcePath.'/'.$this->type.'/'.$this->name;
        foreach ($possibleExtensions as $extreplace) {
            $testfile = preg_replace($extfind,$extreplace,$baseName);
            if (file_exists($testfile)){
                $this->sourcePath = $testfile;
                $this->sourceTime = filemtime($testfile);
                break;
            }
        }
    }

    public function load(){
        if ($this->external || $this->dynamic){
            return;
        }
        if ($this->type == self::STYLE){
            if (preg_match("#less$#",$this->sourcePath)) {
                $lessc = new lessc();
                try {
                    $this->data = $lessc->compileFile($this->sourcePath);
                } catch (\Exception $e) {
                    die($e->getMessage());
                }
            } else if (preg_match("#scss$#",$this->sourcePath)){
                $scss = new scssc();
                $scss->setImportPaths($this->config->sourcePath.'/'.$this->type.'/');
                $this->data = $scss->compile(file_get_contents($this->sourcePath));
            } else if (preg_match("#css$#",$this->sourcePath)){
                $this->data = file_get_contents($this->sourcePath);
            }
        } else if ($this->type == self::SCRIPT) {
            $this->data = file_get_contents($this->sourcePath);
        }
    }

    public function minify(){
        if ($this->external || !$this->minify || $this->dynamic) {
            return;
        }
        if ($this->type == self::STYLE) {
            $this->data = Minify_CSS_Compressor::process($this->data);
        } else if ($this->type == self::SCRIPT){
            $minifiedVersion = preg_replace("#.js$#",'.min.js',$this->sourcePath);
            if (file_exists($minifiedVersion) && filemtime($minifiedVersion) > $this->sourceTime){
                $this->data = file_get_contents($minifiedVersion);
            } else {
                $this->data = JSMin::minify($this->data);
                file_put_contents($minifiedVersion,$this->data);
            }
        }
    }
}