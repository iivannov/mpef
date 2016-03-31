<?php

namespace Iivannov\MPEF;


class MPEFile
{

    protected $key;

    protected $cipher;

    protected $encrypter;

    protected $path;

    protected $data;

    protected $originalExtension;

    protected $originalMimeType;

    protected $originalDataType;

    public function __construct($key = null, $cipher = 'AES-256-CBC')
    {
        $this->key = $key;
        $this->cipher = $cipher;

        $this->setEncrypter();
    }

    //*
    public function write($content)
    {
        $this->data = $this->encrypter->encrypt($this->prepare($content));
    }

    public function file($path)
    {
        $this->originalMimeType = mime_content_type($path);
        $this->originalExtension = pathinfo($path, PATHINFO_EXTENSION);

        $this->write(file_get_contents($path));
    }

    public function save($path = null)
    {

        if(null != $path)
            $this->path = $path;

        try {
            return $this->writeDataToFile($path);
        } catch (\Exception $e) {
            throw new \Exception('Fail saving file:' . $e->getMessage());
        }
    }


    public function read()
    {
        $data = json_decode($this->encrypter->decrypt($this->data));

        return base64_decode($data->data);
    }

    private function prepare($content)
    {
        return json_encode([
            'ext' => $this->originalExtension,
            'mime' => $this->originalMimeType,
            'data' => base64_encode($content)
        ]);
    }


    public static function load($path, $key, $cipher = 'AES-256-CBC')
    {

        try {
            $contents = file_get_contents($path);
        } catch (\Exception $e) {
            throw new \Exception('Fail loading file:' . $e->getMessage());
        }

        $instance = new self($key, $cipher);
        $instance->data = $contents;

        return $instance;
    }

    protected function setEncrypter(EncrypterContract $encrypter = null)
    {
        if (null != $encrypter) {
            $this->encrypter = $encrypter;
        } else {
            $this->encrypter = new \Illuminate\Encryption\Encrypter($this->key(), $this->cipher);
        }
    }

    protected function key()
    {
        if (null == $this->key) {
            $this->key = md5(microtime());
        }

        return $this->key;
    }

    private function writeDataToFile($path)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);

        if(!is_dir($dir))
            mkdir($dir, 0644, true);

        return file_put_contents($path, $this->data);
    }


}

