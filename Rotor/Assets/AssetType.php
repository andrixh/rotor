<?php
namespace Rotor\Assets;

class AssetType
{
    const SCRIPT = 'js';
    const STYLE = 'css';

    public static function getAll(){
        return [
            self::STYLE,
            self::SCRIPT
        ];
    }
}