<?php
namespace Rotor\Assets;

class OutputGroup
{
    protected $name;

    /**
     * @var Asset[]
     */
    protected $assets = [];

    public function addAsset($asset){
        $this->assets[] = $asset;
    }
}