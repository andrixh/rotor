<?php
namespace Rotor\Assets;

use Rotor\Assets\Exception\UnknownAssetTypeException;

class ExposedAssetDefinition {
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var string */
    public $filename;
    /** @var bool */
    public $inline;
    /** @var bool */
    public $external;
    /** @var bool */
    public $combine;
    /** @var bool */
    public $minify;
    /** @var bool */
    public $dynamic;
    /** @var bool */
    public $inHead;
    /** @var array */
    public $dependencies;

    public function __construct($data){
        $this->name = $data['name'];
        $this->filename = $data['filename'];
        $this->type = $data['type'];
        $this->inline = $data['inline'];
        $this->external = $data['external'];
        $this->combine = $data['combine'];
        $this->minify = $data['minify'];
        $this->dynamic = $data['dynamic'];
        $this->inHead = $data['inHead'];
        $this->dependencies = $data['dependencies'];

        if ($this->type === null) {
            throw new UnknownAssetTypeException(sprintf('Cannot determine asset type for %s',$this->filename));
        }
    }
}
