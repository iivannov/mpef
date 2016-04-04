<?php

namespace Iivannov\MPEF;


class MPEFile
{

    /**
     * Encryption key - a random 32 character string
     * used to encrypt and decrypt data
     *
     * @var string
     */
    protected $key;

    /**
     * Encryption cipher used

     * @var string
     */
    protected $cipher;

    /**
     * Instance of an Encryption provider
     *
     * @var EncrypterContract
     */
    protected $encrypter;

    /**
     * The file path of the current file
     *
     * @var string
     */
    protected $path;

    /**
     * The body of the file
     *
     * @var string
     */
    protected $data;

    /**
     * The extension of the original file that was encrypted
     *
     * @var string
     */
    protected $originalExtension;

    /**
     * The MIME type of the original file that was encrypted
     *
     * @var string
     */
    protected $originalMimeType;


    /**
     * MPEFile constructor.
     * @param null $key
     * @param string $cipher
     */
    public function __construct($key = null, $cipher = 'AES-256-CBC')
    {
        $this->key = $key;
        $this->cipher = $cipher;

        $this->setEncrypter();
    }

    /**
     * Reads the data stored in the encrypted file
     *
     * @return string
     */
    public function read()
    {
        $data = json_decode($this->encrypter->decrypt($this->data));

        return base64_decode($data->data);
    }

    /**
     * Encrypts the content and store it
     *
     * @param $content
     */
    public function write($content)
    {
        $this->data = $this->encrypter->encrypt($this->prepare($content));
    }

    /**
     * Encrypts the contents of a given file
     *
     * @param $path
     */
    public function file($path)
    {
        $this->originalMimeType = mime_content_type($path);
        $this->originalExtension = pathinfo($path, PATHINFO_EXTENSION);

        $this->write(file_get_contents($path));
    }

    /**
     * Saves the file to a given location
     *
     * @param null $path
     * @return int
     * @throws \Exception
     */
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


    /**
     * Formats the data and all the additional info
     * before encrypting it
     *
     * @param $content
     * @return string
     */
    private function prepare($content)
    {
        return json_encode([
            'ext' => $this->originalExtension,
            'mime' => $this->originalMimeType,
            'data' => base64_encode($content)
        ]);
    }


    /**
     * Loads the contents of a MPEF file
     *
     * @param $path
     * @param $key
     * @param string $cipher
     * @return MPEFile
     * @throws \Exception
     */
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

    /**
     * Setter for the Encryption provider
     * @param EncrypterContract|null $encrypter
     */
    protected function setEncrypter(EncrypterContract $encrypter = null)
    {
        if (null != $encrypter) {
            $this->encrypter = $encrypter;
        } else {
            $this->encrypter = new \Illuminate\Encryption\Encrypter($this->key(), $this->cipher);
        }
    }

    /**
     * Returns the encryption key for the file
     *
     * @return string
     */
    protected function key()
    {
        if (null == $this->key) {
            $this->key = md5(microtime());
        }

        return $this->key;
    }

    /**
     * Helper method to write the file in the filesystem
     *
     * @param $path
     * @return int
     */
    private function writeDataToFile($path)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);

        if(!is_dir($dir))
            mkdir($dir, 0644, true);

        return file_put_contents($path, $this->data);
    }


}

