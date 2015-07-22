<?php
namespace Rotor\Assets\Minifier;

use Minify_CSS_Compressor;

class MinifyCssCompressorMinifier extends BaseMinifier{
    protected $extension = 'css';

    public function minify($source) {
        return Minify_CSS_Compressor::process($source);
    }
}