<?php
namespace Rotor\Assets\Minifier;

use JSMin;

class JsminMinifier extends BaseMinifier{
    protected $extension = 'js';

    public function minify($source) {
        return JSMin::minify($source);
    }
}