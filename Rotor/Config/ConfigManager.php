<?php
namespace Rotor\Config;

use Rotor\Path;

class ConfigManager {

    /**
     * @var Path
     */
    protected $configDir;
    /** @var  string */
    protected $environment;

    /** @var  ConfigValue[] */
    protected $values = [];

    public function __construct($configDir, $environment) {
        $this->configDir = $configDir;
        $this->environment = $environment;
        $this->loadValues();
    }

    protected function loadValues(){
        $files = glob($this->configDir->append('*.conf.php'));
        $envFiles = glob($this->configDir->append('*.conf.'.$this->environment.'.php'));

        $configFiles = array_merge($files,$envFiles);

        $values = [];
        foreach($configFiles as $file) {
            $values = array_merge($values, include $file);
        }
        foreach ($values as $key=>$value){
            $this->add($key,$value);
        }
    }

    protected function add($key,$value) {
        $public = false;
        if (substr($key,0,1) == '@') {
            $public = true;
            $key = substr($key,1);
        }
        if (substr($key,0,5) == 'path.') {
            $value = Path::Create($value);
        }
        $this->values[$key] = new ConfigValue($value,$public);
    }

    public function get($key) {
        if (array_key_exists($key,$this->values)) {
            return $this->values[$key]->getValue();
        } else {
            throw new ConfigException(sprintf('Configuration key "%s" not found',$key));
        }
    }

    public function has($key) {
        return array_key_exists($key,$this->values);
    }

    public function getAllPublic(){
        $result = [];
        foreach ($this->values as $key=>$value) {
            if ($value->isPublic()) {
                $result[$key] = $value->getValue();
            }
        }
        return $result;
    }

    public function getAll(){
        return $this->values;
    }
}