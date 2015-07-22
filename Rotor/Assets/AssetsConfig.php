<?php
namespace Rotor\Assets;

use Rotor\Assets\Compiler\CompilerInterface;
use Rotor\Path;

class AssetsConfig {
    /** @var Path */
    public $sourcePath='';

    /** @var Path */
    public $outputPath='';

    /** @var bool */
    public $minify=false;

    /** @var bool */
    public $combine=false;

    /** @var bool */
    public $gzip=false;

    /** @var bool */
    public $forceRecompile=false;

}