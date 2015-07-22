<?php
namespace Rotor\Assets\Compiler;

interface CompilerInterface {

    /**
     * @param array $options
     */
    public function __construct($options=[]);

    /**
     * @return string
     */
    public function getInputExtension();

    /**
     * @return string
     */
    public function getOutputExtension();

    /**
     * @param $filename string
     * @return string
     */
    public function suggestSource($filename);

    /**
     * @param $source string
     * @return string
     */
    public function compile($source);

}