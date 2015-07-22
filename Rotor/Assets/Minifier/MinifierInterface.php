<?php
namespace Rotor\Assets\Minifier;

interface MinifierInterface {

    public function __construct($options=[]);

    /**
     * @return string
     */
    public function getExtension();

    /**
     * @param string $source
     * @return string
     */
    public function minify($source);
}