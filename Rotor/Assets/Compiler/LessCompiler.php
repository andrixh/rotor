<?php
namespace Rotor\Assets\Compiler;

use Rotor\Assets\Compiler\CompilerInterface;

class LessCompiler extends BaseCompiler{

    protected $inputExtension = 'less';
    protected $outputExtension = 'css';

    protected $lessc = null;

    /**
     * @param $source string
     * @return mixed
     */
    public function compile($source)
    {
        if ($this->lessc === null){
            $this->lessc = new \lessc();
        }
        return $this->lessc->compileFile($source);
    }
}