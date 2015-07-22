<?php
namespace Rotor;

class RequestFile {
    protected $name;
    protected $type;
    protected $tmp_name;
    protected $error;
    protected $size;

    public static function FromActualFile($filename){
        $result = null;
        if (file_exists($filename)){
            $name = pathinfo($filename,PATHINFO_FILENAME);
            $extension = pathinfo($filename,PATHINFO_EXTENSION);
            $tmp_name = $filename;
            $size = filesize($filename);

            $result = new RequestFile($name,$extension,$tmp_name,UPLOAD_ERR_OK,$size);
        }
        return $result;
    }


    public function __construct($name,$extension,$tmp_name,$error,$size){
        $this->name = $name;
        $this->extension = $extension;
        $this->tmp_name = $tmp_name;
        $this->error = $error;
        $this->size = $size;
        $this->detectType();
    }


    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getTmpName()
    {
        return $this->tmp_name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function getExtension(){
        return $this->extension;
    }

    public function detectType(){
        $result = false;
        if ($this->isOk()){
            $this->type = Mimes::getType($this->extension);
            $result = $this->type;
        }
        return $result;
    }

    public function isOk(){
        return $this->error == UPLOAD_ERR_OK;
    }

    public function discard(){
        unlink ($this->tmp_name);
    }

    public function copy($dir,$filename){
        if (!is_uploaded_file($this->name)){
            return false;
        }
        return move_uploaded_file($this->tmp_name,static::fixPath($dir.'/'.$filename));
    }


	//helper methods
	private static function fixPath($value){
		$fixed = preg_replace("#/+#",'/',$value);
		$realPath = self::realPath($fixed);
		return $realPath;
	}

	private static function realPath($path){
		if (strpos($path,'..')===false) {
			$result = $path;
		} else {
			$parts = explode('/',$path);
			$result = [];
			foreach ($parts as $part){
				if ($part == '..'){
					array_pop($result);
				} else {
					$result[] = $part;
				}
			}
			$result = implode('/',$result);
		}
		return $result;
	}
}