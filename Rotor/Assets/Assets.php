<?php
namespace Rotor\Assets;

use Rotor\Assets\Compiler\CompilerInterface;
use Rotor\Assets\Minifier\MinifierInterface;

class Assets
{

    /**
     * @var AssetManager
     */
    public static $instance = null;

    public static function Init(AssetsConfig $config)
    {
        if (static::$instance === null) {
            static::$instance = new AssetManager($config);
        }
        return static::$instance;
    }

    public static function add($filename){
        $newDefinition = new AssetDefinition($filename);
        static::$instance->addDefinition($newDefinition);
        return $newDefinition;
    }

    public static function require_asset($asset)
    {
        self::$instance->require_asset($asset);
    }

    public static function generateHeadAssets()
    {
        return self::$instance->generateHeadAssets();
    }

    public static function generateBottomAssets()
    {
        return self::$instance->generateBottomAssets();
    }

    /**
     * @param CompilerInterface $compiler
     */
    public static function registerCompiler(CompilerInterface $compiler){
        self::$instance->registerCompiler($compiler);
    }

    /**
     * @param MinifierInterface $minifier
     */
    public static function registerMinifier(MinifierInterface $minifier){
        self::$instance->registerMinifier($minifier);
    }
}
