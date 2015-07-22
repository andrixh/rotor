<?php
namespace Rotor;

use Rotor\Exception\InvalidPathOperationException;

class Path
{
    protected $is_empty = false;
    protected $is_dir = false;
    protected $is_absolute = false;
    protected $is_file = false;

    protected $directory = '';
    protected $basename = '';
    protected $filename = '';
    protected $extension = '';

    protected static $dirCache = [];

    /**
     * @param string $pathStr
     * @return Path
     */
    public static function Create($pathStr)
    {
        return new Path($pathStr);
    }

    public static function CreateDir($pathStr)
    {
        return new Path($pathStr, true);
    }

    /**
     * @param string $pathStr
     * @param bool $as_directory
     */
    public function __construct($pathStr, $as_directory = false)
    {
        $hasLeadingSlash = substr($pathStr, 0, 1) == '/';
        $hasTrailingSlash = mb_substr($pathStr, -1) == '/';

        $this->is_dir = $pathStr == '' || $hasTrailingSlash || $as_directory;
        $this->is_absolute = $hasLeadingSlash;

        $parts = explode('/', $pathStr);

        $pathParts = [];
        foreach ($parts as $part) {
            if ($part != '') {
                if ($part == '..') {
                    array_pop($pathParts);
                } else if ($part != '.') {
                    $pathParts[] = $part;
                }
            }
        }

        if (!$this->is_dir) {
            $filePart = array_pop($pathParts);
            $this->is_file = true;

            $basename = $filePart;
            $fileParts = explode('.',$basename);
            $extension = '';
            if (count($fileParts)>0) {
                if (count($fileParts)>1) {
                    $extension = array_pop($fileParts);
                }
            }

            $filename = implode('.',$fileParts);
            if ($filename == '' && $extension != '') {
                $filename = '.'.$extension;
                $extension = '';
            }
            $this->basename = $basename;
            $this->filename = $filename;
            $this->extension = $extension;
        }

        $this->directory = ($hasLeadingSlash?'/':'').implode('/',$pathParts).'/';
        if ($this->directory == '//') {
            $this->directory = '/';
        }
    }

    public function isDirectory()
    {
        return $this->is_dir;
    }

    public function isAbsolute(){
        return $this->is_absolute;
    }

    public function isFile(){
        return $this->is_file;
    }

    public function basename($baseName = null){
        if ($baseName === null) {
            return $this->basename;
        }
        return new Path($this->directory.$baseName);
    }

    public function extension($ext = null){
        if ($ext === null) {
            return $this->extension;
        }
        return new Path($this->directory.$this->filename.($ext?'.'.$ext:''));
    }

    public function filename($filename = null){
        if ($filename === null) {
            return $this->filename;
        }
        return new Path($this->directory.$filename.($this->extension?'.'.$this->extension:''));
    }

    public function directory($directory=null){
        if ($directory === null) {
            return $this->directory();
        }
        return new Path($directory.'/'.$this->filename.($this->extension?'.'.$this->extension:''));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->directory.$this->basename;
    }

    /**
     * @param string $str
     * @return Path
     * @throws InvalidPathOperationException;
     */
    public function append($str)
    {
        if ($this->is_file) {
            throw new InvalidPathOperationException('Cannot append a path after a filename');
        }
        return new Path($this->directory.'/'.$str);
    }

    /**
     * @param string $str
     * @return Path
     */
    public function prepend($str){
        return new Path($str.'/'.$this->__toString());
    }


    public function inWebRoot(){
        return substr($this->__toString(),0,mb_strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'];
    }

    public function webPath(){
        if (!$this->inWebRoot()) {
            throw new PathNotInWebRootException();
        }
        return new Path('/'.substr($this->__toString(),mb_strlen($_SERVER['DOCUMENT_ROOT'])),$this->is_dir);
    }

}