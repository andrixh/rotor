<?php
namespace Rotor\Assets\Compiler;

abstract class BaseCompiler implements CompilerInterface
{
    protected $options = [];
    protected $inputExtension;
    protected $outputExtension;

    public function __construct($options=[]) {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getInputExtension()
    {
        return $this->inputExtension;
    }

    /**
     * @return string
     */
    public function getOutputExtension(){
        return $this->outputExtension;
    }

    /**
     * @param $filename
     * @return mixed
     */
    public function suggestSource($filename){
        $findRegex = "/\\.".$this->outputExtension."$/";
        $replace = '.'.$this->inputExtension;
        return preg_replace($findRegex,$replace,$filename);
    }

    abstract public function compile($source);
}
