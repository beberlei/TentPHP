<?php

namespace TentPHP;

abstract class DataObject
{
    protected $data;

    public function __call($method, $args)
    {
        $type = substr($method, 0, 3);
        $name = lcfirst(substr($method, 3));

        if (!array_key_exists($name, $this->data)) {
            throw new \BadMethodCallException("No method " . $method);
        }

        if ($type == "get") {
            return $this->data[$name];
        } else if ($type == "set") {
            $this->data[$name] = $args[0];
            return $this;
        }

        throw new \BadMethodCallException("No method " . $method);
    }
}

