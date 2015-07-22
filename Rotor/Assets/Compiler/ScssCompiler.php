<?php
namespace Rotor\Assets\Compiler;

use Rotor\Assets\Compiler\CompilerInterface;
use scssc;

class ScssCompiler extends BaseCompiler{

    const OPTION_IMPORT_PATH='import.path';

    protected $inputExtension = 'scss';
    protected $outputExtension = 'css';

    protected $scss = null;

    /**
     * @param $source string
     * @return mixed
     */
    public function compile($source)
    {
        if ($this->scss === null){
            $this->scss = new scssc();
            $this->scss->setImportPaths($this->options[static::OPTION_IMPORT_PATH]);
        }
        return $this->scss->compile($source);
    }
}