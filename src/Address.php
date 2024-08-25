<?php

namespace FilippoToso\EmlBuilder;

class Address
{
    protected $address = null;
    protected $name = null;

    public function __construct($address, $name = null)
    {
        $this->address = $address;
        $this->name = $name;
    }

    public static function make($address, $name = null)
    {
        return new static($address, $name);
    }

    public function __get($name)
    {
        if (in_array($name, ['address', 'name'])) {
            return $this->$name;
        }

        return null;
    }

    public function get()
    {
        if (is_null($this->name)) {
            return $this->address;
        }

        return sprintf('"%s" <%s>', $this->name, $this->address);
    }
}
