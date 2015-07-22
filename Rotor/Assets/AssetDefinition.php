<?php
namespace Rotor\Assets;

use Rotor\Assets\ExposedAssetDefinition;

class AssetDefinition
{
    /** @var string */
    protected $name; //defaults to filename, can be an arbitrary alias string
    /** @var string */
    protected $type; //JS or CSS,
    /** @var string */
    protected $filename; //real filename, regardless of $name
    /** @var bool */
    protected $inline = false;
    /** @var bool */
    protected $external = false;
    /** @var bool */
    protected $combine = true;
    /** @var bool */
    protected $minify = true;
    /** @var bool */
    protected $dynamic = false;
    /** @var bool */
    protected $inHead = false; //only meaningful for scripts
    /** @var string[] */
    protected $dependencies = [];

    public function __construct($filename)
    {
        $this->filename($filename);
        $this->name($filename);

        return $this;
    }

    public function filename($filename = null)
    {
        if ($filename === null) {
            return $this->filename;
        }
        $this->filename = $filename;

        $filenameParts = explode('?', $filename);

        foreach (AssetType::getAll() as $type) {
            if (mb_substr($filenameParts[0], -mb_strlen($type)) == $type) {
                $this->type = $type;
                break;
            }
        }

        $externalUrls = ['//', 'http://', 'https://'];

        foreach ($externalUrls as $url) {
            if (substr($this->filename, 0, strlen($url)) == $url) {
                $this->inline = false;
                $this->external = true;
                $this->minify = false;
                $this->combine = false;
                break;
            }
        }

        return $this;
    }

    /**
     * @param null $name
     * @return $this|string
     */
    public function name($name = null)
    {
        if ($name === null) {
            return $this->name;
        }
        $this->name = $name;
        return $this;
    }

    /**
     * @param null $type
     * @return $this|string
     */
    public function type($type = null)
    {
        if ($type === null) {
            return $this->type;
        }
        $this->type = $type;
        return $this;
    }

    /**
     * @return $this
     */
    public function inline()
    {
        $this->inline = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function external()
    {
        $this->external = true;
        return $this;
    }

    public function standAlone()
    {
        $this->combine = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function noMinify()
    {
        $this->minify = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function placeInHead()
    {
        $this->inHead = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function dynamic()
    {
        $this->dynamic = true;
        return $this;
    }

    /**
     * @param null $dependencies
     * @return \string[]|$this
     */
    public function requires($dependencies = null)
    {
        if ($dependencies == null) {
            return $this->dependencies;
        }

        $args = func_get_args();
        if (count($args) == 1) {
            if (is_array($args[0])) {
                foreach ($args[0] as $dep) {
                    $this->dependencies[] = trim($dep);
                }
            } elseif (is_string($args[0])) {
                $deps = explode(',', $args[0]);
                foreach ($deps as $dep) {
                    $this->dependencies[] = trim($dep);
                }
            }

        } else {
            foreach ($args as $arg) {
                $this->dependencies[] = trim($arg);
            }
        }
        return $this;
    }

    /**
     * @return ExposedAssetDefinition
     */
    public function retreiveData()
    {
        return new ExposedAssetDefinition([
            "name" => $this->name,
            "type" => $this->type,
            "filename" => $this->filename,
            "inline" => $this->inline,
            "external" => $this->external,
            "combine" => $this->combine,
            "minify" => $this->minify,
            "dynamic" => $this->dynamic,
            "inHead" => $this->inHead,
            "dependencies" => $this->dependencies
        ]);
    }
}