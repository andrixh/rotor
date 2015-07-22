<?php
namespace Rotor\Assets;

use Rotor\Environment;
//use Rotor\Config;
use lessc;
use scssc;
use Minify_CSS_Compressor;
use JSMin;

class Asset
{
    /**
     * @var AssetManager
     */
    protected $manager;

    protected $name;
    protected $filename;
    protected $type;
    protected $inline = false;
    protected $external = false;
    protected $combine = true;
    protected $minify = true;
    protected $dynamic = false;
    protected $inHead = false; //only meaningful for scripts

    protected $data = '';

    protected $sourcePath = '';
    protected $sourceTime = 0;


    /**
     * @param ExposedAssetDefinition $definition
     * @param AssetManager $manager
     */
    public function __constructs(ExposedAssetDefinition $definition, AssetManager $manager)
    {
        $this->manager = $manager;

        $this->name = $definition->name;
        $this->filename = $definition->filename;
        $this->type = $definition->type;
        $this->inline = $definition->inline;
        $this->external = $definition->external;
        $this->combine = $definition->combine;
        $this->minify = $definition->minify;
        $this->inHead = $definition->inHead;
    }


    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isInline()
    {
        return $this->inline;
    }

    public function isExternal()
    {
        return $this->external;
    }

    public function canCombine()
    {
        return $this->combine && !$this->dynamic;
    }

    public function canMinify()
    {
        return $this->minify && !$this->dynamic;
    }

    public function inHead()
    {
        return $this->inHead;
    }

    public function isDynamic()
    {
        return $this->dynamic;
    }

    public function getSourceTime()
    {
        return $this->sourceTime;
    }

    public function getData()
    {
        return $this->data;
    }

    public function findSource()
    {
        if ($this->external) {
            $this->sourcePath = $this->filename;
            return;
        }

        /** @var Path $sourcePath */
        $sourcePath = $this->manager->config->sourcePath;
        $fileSources = [];
        foreach($this->manager->compilers[$this->type] as $compiler){
            $fileSources[] = $sourcePath->append(
              mb_substr($this->filename,)
            );
        }

        $envPrefix = '.' . Environment::get();
        if ($this->type == AssetType::SCRIPT) {
            $possibleExtensions = [$envPrefix . '.js', '.js'];
            $extfind = "#\\.js$#";
        } else if ($this->type == AssetType::STYLE) {
            $possibleExtensions = [$envPrefix . '.css', '.css', $envPrefix . '.less', '.less', $envPrefix . '.scss', '.scss'];
            $extfind = '#\\.css$#';
        }

        $baseName = $this->manager->config->sourcePath . '/' . $this->type . '/' . $this->name;
        foreach ($possibleExtensions as $extreplace) {
            $testfile = preg_replace($extfind, $extreplace, $baseName);
            if (file_exists($testfile)) {
                $this->sourcePath = $testfile;
                $this->sourceTime = filemtime($testfile);
                break;
            }
        }
    }

    public function load()
    {
        if ($this->external || $this->dynamic) {
            return;
        }
        if ($this->type == AssetType::STYLE) {
            if (preg_match("#less$#", $this->sourcePath)) {
                $lessc = new lessc();
                try {
                    $this->data = $lessc->compileFile($this->sourcePath);
                } catch (\Exception $e) {
                    die($e->getMessage());
                }
            } else if (preg_match("#scss$#", $this->sourcePath)) {
                $scss = new scssc();
                $scss->setImportPaths($this->manager->config->sourcePath . '/' . $this->type . '/');
                $this->data = $scss->compile(file_get_contents($this->sourcePath));
            } else if (preg_match("#css$#", $this->sourcePath)) {
                $this->data = file_get_contents($this->sourcePath);
            }
        } else if ($this->type == AssetType::SCRIPT) {
            $this->data = file_get_contents($this->sourcePath);
        }
    }

    public function minify()
    {
        if ($this->external || !$this->minify || $this->dynamic) {
            return;
        }
        if ($this->type == AssetType::STYLE) {
            $this->data = Minify_CSS_Compressor::process($this->data);
        } else if ($this->type == AssetType::SCRIPT) {
            $minifiedVersion = preg_replace("#.js$#", '.min.js', $this->sourcePath);
            if (file_exists($minifiedVersion) && filemtime($minifiedVersion) > $this->sourceTime) {
                $this->data = file_get_contents($minifiedVersion);
            } else {
                $this->data = JSMin::minify($this->data);
                file_put_contents($minifiedVersion, $this->data);
            }
        }
    }
}