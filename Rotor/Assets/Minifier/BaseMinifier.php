<?php
namespace Rotor\Assets\Minifier;

abstract class BaseMinifier implements MinifierInterface
{
    protected $options = [];
    protected $extension;

    public function __construct($options=[]) {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    abstract public function minify($source);
}
