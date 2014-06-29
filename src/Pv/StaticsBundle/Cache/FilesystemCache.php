<?php

namespace Pv\StaticsBundle\Cache;

class FilesystemCache
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function has($key)
    {
        return file_exists($this->dir.'/'.$key);
    }

    public function get($key)
    {
        $path = $this->dir.'/'.$key;

        if (!file_exists($path)) {
            return null;
        }

        return unserialize(file_get_contents($path));
    }

    public function set($key, $value)
    {
        if (!is_dir($this->dir) && false === @mkdir($this->dir, 0777, true)) {
            throw new \RuntimeException('Unable to create directory '.$this->dir);
        }

        $path = $this->dir.'/'.$key;

        if (false === @file_put_contents($path, serialize($value))) {
            throw new \RuntimeException('Unable to write file '.$path);
        }
    }

    public function remove($key)
    {
        $path = $this->dir.'/'.$key;

        if (file_exists($path) && false === @unlink($path)) {
            throw new \RuntimeException('Unable to remove file '.$path);
        }
    }
}
