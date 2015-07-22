<?php
namespace Rotor\Config;

class ConfigValue
{
    /** @var bool */
    protected $public;
    /** @var mixed */
    protected $value;

    /**
     * @param mixed $value
     * @param bool $private
     */
    public function __construct($value, $public)
    {
        $this->value = $value;
        $this->public = $public;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}