<?php

/**
 *
 * @author <yoterri@ideasti.com>
 * @copyright 2014
 */

/**

 $postedFile = new PostedFile($_FILES);
 $fileSaver = new = FileSaver($postedFile);

 $fileSaver->setUploadPath('/full/path/to/upload/directory');

 //path to the container folder of the uploaded file
 $fileSaver->setContainerDirectory('directory_name');
 $fileSaver->setAllowedExtensions(array('jpg', 'pdf'));
 $fileSaver->setAllowedType(
    array('image/jpeg' => 'jpg'
    ,'application/pdf' => 'pdf')
 );

 $flag = $fileSaver->check();
 if($uploaded)
 {
    $filename = $fileSaver->saveAs();
    if($filename)
    {
        // relative path to the uploaded file
        #$filename;
        
        // 
        #$com = $fileSaver->getCommunicator();
        #$com->getData('file_name');
        #$com->getData('full_path');
        #$com->getData('relative_path');
    }   
 }
 
 if(!$uploaded)
 {
    $errors = $fileSaver->getCommunicator()->getErros();
 }
*/

namespace Com;

class FileSaver
{
    
    /**
     *
     * @var string
     */
    const UNEXPECTED_ERROR = 'Unexpected error';
    
    /**
     * No hay error, archivo subido con exito.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_OK = 0;
    
    /**
     * El archivo subido excede la directiva upload_max_filesize en php.ini.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_INI_SIZE = 1;

    protected $RESPONSE_1 = 'Uploaded file size is bigger than max allowed in the php.ini configuration.';
    
    /**
     * El archivo subido excede la directiva MAX_FILE_SIZE que fue especificada en el formulario HTML.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_FORM_SIZE = 2;

    protected $RESPONSE_2 = 'Uploaded file size is bigger than max allowed.';
    
    /**
     * El archivo subido fue salo parcialmente cargado.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_PARTIAL = 3;

    protected $RESPONSE_3 = 'File was uploaded partially.';
    
    /**
     * Ningun archivo fue subido.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_NO_FILE = 4;

    protected $RESPONSE_4 = 'No file uploaded.';
    
    /**
     * Falta la carpeta temporal.
     * Introducido en PHP 4.3.10 y PHP 5.0.3.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_NO_TMP_DIR = 5;
    const RESPONSE_5 = 'Temporary folder not found.';
    
    /**
     * No se pudo escribir el archivo en el disco.
     * Introducido en PHP 5.1.0.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_CANT_WRITE = 6;

    protected $RESPONSE_6 = 'Cannot save the file in the hard disc.';
    
    /**
     * Una extensión de PHP detuvo la carga de archivos.
     * PHP no proporciona una forma de determinar cual extensión causó la parada de la subida de archivos;
     * el examen de la lista de extensiones cargadas con phpinfo() puede ayudar. Introducido en PHP 5.2.0.
     *
     * @var int
     */
    const RESPONSE_UPLOAD_ERR_EXTENSION = 7;

    protected $RESPONSE_7 = 'Unexpected error with some php extensions.';
    
    /**
     *
     * @var string
     */
    const RESPONSE_EXTENSION_NOT_ALLOWED = 'File extension not allowed. Expected: ###.';
    
    /**
     *
     * @var string
     */
    const RESPONSE_MIME_TYPE_NOT_ALLOWED = 'File type not allwed. Expected: ###';
    
    /**
     *
     * @var string
     */
    const RESPONSE_UPLOAD_DIRECTORY_NOT_EXIST = "Upload directory doesn't exist.";
    
    /**
     *
     * @var string
     */
    const RESPONSE_UPLOAD_DIRECTORY_NOT_WRITABLE = "Upload directory is not writable.";
    
    /**
     *
     * @var string
     */
    const RESPONSE_NOT_VALID_UPLOADED_FILE = 'Uploadedfile is not valid.';
    
    /**
     *
     * @var string
     */
    const RESPONSE_CANNOT_MOVE_UPLOADED_FILE = 'Cannot move uploaded file.';

    /**
     * listado de las extensiones permitidas
     *
     * @var array
     */
    protected $_allowedExtensions = array();

    /**
     *
     * @var array
     */
    protected $_allowedTypes;

    /**
     *
     * @var bool
     */
    protected $_encloseWithDate = true;

    /**
     *
     * @var string
     */
    protected $_uploadPath;

    /**
     *
     * @var string
     */
    protected $_containerDirectory;

    /**
     *
     * @var bool
     */
    protected $_useRandFileName = true;

    /**
     *
     * @var int
     */
    protected $_maxFileSize = - 1;

    /**
     *
     * @var \Com\PostedFile
     */
    protected $_postedFile;

    /**
     *
     * @var \Com\Communicator
     */
    protected $_communicator;


    /**
     *
     * @param \Com\PostedFile|\Com\StreamedFile $postedFile
     */
    function __construct($postedFile = null)
    {
        if(! is_null($postedFile))
        {
            $this->setPostedFile($postedFile);
        }
    }


    /**
     *
     * @param string $message
     * @return \Com\Communicator
     */
    protected function _setCommunicatorMessage($message)
    {
        $this->getCommunicator()->addError($message);
        
        return $this;
    }


    /**
     *
     * @return \Com\Communicator
     */
    function getCommunicator()
    {
        if(! $this->_communicator instanceof \Com\Communicator)
            $this->_communicator = new \Com\Communicator();
        
        return $this->_communicator;
    }


    /**
     *
     * @return \Com\FileSaver
     */
    function resetCommunicator()
    {
        $this->_communicator = null;
        
        return $this;
    }


    /**
     *
     * @param \Com\PostedFile|\Com\StreamedFile $postedFile
     * @return \Com\FileSaver
     */
    function setPostedFile($postedFile)
    {
        if($postedFile instanceof \Com\PostedFile)
        {
            $this->_postedFile = $postedFile;
        }
        elseif($postedFile instanceof \Com\StreamedFile)
        {
            $this->_postedFile = $postedFile;
        }
        
        return $this;
    }


    /**
     *
     * @return \Com\PostedFile
     */
    function getPostedFile()
    {
        return $this->_postedFile;
    }


    /**
     *
     * @param bool $value
     * @return \Com\FileSaver
     */
    function setUseRandFileName($value)
    {
        $this->_useRandFileName = (bool)$value;
        return $this;
    }


    /**
     *
     * @return boolean
     */
    function getUseRandFileName()
    {
        return $this->_useRandFileName;
    }


    /**
     *
     * @return string
     */
    public function getUploadPath()
    {
        return $this->_uploadPath;
    }


    /**
     *
     * @param string $uploadPath
     * @return \Com\FileSaver
     */
    public function setUploadPath($uploadPath)
    {
        $this->_uploadPath = $uploadPath;
        return $this;
    }


    /**
     *
     * @return string
     */
    public function getContainerDirectory()
    {
        return $this->_containerDirectory;
    }


    /**
     *
     * @param string $containerDirectory
     * @return \Com\FileSaver
     */
    public function setContainerDirectory($containerDirectory)
    {
        $this->_containerDirectory = $containerDirectory;
        return $this;
    }


    /**
     *
     * @return string
     */
    function getFullPathToUpload()
    {
        $path = $this->getUploadPath();
        if(! file_exists($path))
            return '';
        
        $relPath = $this->getRelativePath();
        if(! empty($relPath))
            $relPath = "/$relPath";
        
        return $path . $relPath;
    }


    /**
     *
     * @return string
     */
    function getRelativePath()
    {
        $path = $this->getUploadPath();
        if(! file_exists($path) || ! is_writable($path))
            return '';
        
        $new_umask = 0777;
        #$old_umask = umask($new_umask);
        
        $containerDirectory = $this->getContainerDirectory();
        if(! empty($containerDirectory))
        {
            $path .= ('/' . $containerDirectory);
            if(! file_exists($path))
            {
                mkdir($path, $new_umask, true);
                chmod($path, $new_umask);
            }
        }
        
        if($this->getEncloseWithDate())
        {
            $path .= ('/' . $this->getDateDirectory());
            if(! file_exists($path))
            {
                mkdir($path, $new_umask, true);
                chmod($path, $new_umask);
            }
        }
        
        $r = '';
        if($containerDirectory)
        {
            $r .= "$containerDirectory";
        }
        
        if($this->getEncloseWithDate())
        {
            if(! empty($r))
            {
                $r .= '/';
            }
            
            $r .= $this->getDateDirectory();
        }
        
        return $r;
    }


    /**
     *
     * @return boolean
     */
    public function getEncloseWithDate()
    {
        return $this->_encloseWithDate;
    }


    /**
     *
     * @param bool $encloseWithDate
     * @return \Com\FileSaver
     */
    public function setEncloseWithDate($encloseWithDate)
    {
        $this->_encloseWithDate = $encloseWithDate;
        return $this;
    }


    /**
     *
     * @param array $allowedExtensions
     * @return \Com\FileSaver
     */
    function setAllowedExtensions(array $allowedExtensions)
    {
        $this->_allowedExtensions = $allowedExtensions;
        return $this;
    }


    /**
     *
     * @return \Com\FileSaver
     */
    function setAllowImagesForWeb()
    {
        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'gif' 
        ];
        
        $allowedTypes = [
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/gif' 
        ];
        
        $this->setAllowedExtensions($allowedExtensions);
        $this->setAllowedType($allowedTypes);
        
        return $this;
    }


    /**
     *
     * @return array
     */
    function getAllowedExtensions()
    {
        return $this->_allowedExtensions;
    }


    /**
     *
     * @return \Com\FileSaver
     */
    function setAllowedType(array $types)
    {
        $this->_allowedTypes = $types;
        return $this;
    }


    /**
     *
     * @return array
     */
    function getAllowedType()
    {
        return $this->_allowedTypes;
    }


    /**
     *
     * @param string $newExtension si es nulo se mantiene la extension original.
     * @param int $min
     * @param int $max
     * @param int $type
     * @return string
     */
    function getRandomFilename($newExtension = null, $min = 3, $max = 9, $type = \Com\Random::TYPE_NUMERIC)
    {
        $rand = new \Com\Random(array(
            'min' => $min,
            'max' => $max,
            'type' => $type 
        ));
        
        $uploadDir = $this->getFullPathToUpload();
        if(! empty($uploadDir))
            $uploadDir .= '/';
        
        $ext = '';
        if(is_null($newExtension))
        {
            $ext = ".{$this->getPostedFile()->getExtension()}";
        }
        else
        {
            if(substr($newExtension, 0, 1) != '.')
                $newExtension = ".$newExtension";
            
            $ext = $newExtension;
        }
        
        $c = 0;
        do
        {
            $c ++;
            
            if(10 == $c)
                throw new \Exception('The system cannot generate a random filename.');
            
            $randFilename = $rand->get();
        }
        while(file_exists($uploadDir . $randFilename . $ext));
        
        return $randFilename . $ext;
    }


    /**
     *
     * @param string $newName
     * @return bool
     */
    function saveAs($newName = null)
    {
        $postedFile = $this->getPostedFile();
        
        if(empty($newName))
        {
            if($this->getUseRandFileName())
            {
                $newName = $this->getRandomFilename();
            }
            else
            {
                $newName = $postedFile->getName();
            }
        }
        
        $filename = $this->getFullPathToUpload() . '/' . $newName;

        if($postedFile instanceof \Com\PostedFile)
        {
            $f = @move_uploaded_file($postedFile->getTmpName(), $filename);
        }
        elseif($postedFile instanceof \Com\StreamedFile)
        {
            $f = rename($postedFile->getTmpName(), $filename);
        }
        else
        {
            $f = false;
        }
        
        if(!$f)
        {
            $this->_setCommunicatorMessage(self::RESPONSE_CANNOT_MOVE_UPLOADED_FILE, 'error');
            return false;
        }
        else
        {
            $fullPath = $this->getFullPathToUpload();
            $relPath = $this->getRelativePath();

            $arr = array(
                'file_name' => $newName,
                'full_path' => "{$fullPath}/{$newName}",
                'relative_path' => "{$relPath}/{$newName}",
            );

            $fileName = $postedFile->getName();
            $this->getCommunicator()->setSuccess("File '$fileName' successful uploaded.", $arr);

            #
            $f = "{$relPath}/{$newName}";
            $newUmask = 0777;
            $oldUmask = umask($newUmask);
            
            chmod($filename, $newUmask);
            umask($oldUmask);
        }
        
        return $f;
    }


    /**
     *
     * @return boolean
     */
    function check()
    {
        $postedFile = $this->getPostedFile();
        
        // las verificaciones por defecto de php
        if($postedFile instanceof \Com\PostedFile)
        {
            $error = $postedFile->getError();
            if($error >= 1 && $error <= 7)
            {
                $var = "RESPONSE_{$error}";
                $message = $this->$var;
                
                if(1 == $error)
                {
                    $upload_max_filesize = ini_get('upload_max_filesize');
                    $bytes = \Com\Util\MaxUploadFileSize::normalice($upload_max_filesize);
                    
                    $message .= ' ' . $this->_fileSize($bytes, null, 1);
                }
                
                $this->_setCommunicatorMessage($message, 'error');
                return false;
            }
        }
        
        
        // verificar la extension del archivo
        $allowed = $this->getAllowedExtensions();
        
        if(count($allowed))
        {
            if(!$postedFile->hasExtension($allowed))
            {
                $str = sprintf('(%s)', implode(', ', $allowed));
                $msg = str_replace('###', $str, self::RESPONSE_EXTENSION_NOT_ALLOWED);
                $this->_setCommunicatorMessage($msg, 'error');
                return false;
            }
        }
        
        $allowed = $this->getAllowedType();
        if(count($allowed))
        {
            $isAllowed = false;
            foreach($allowed as $mime => $ext)
            {
                if(strtolower($ext) == strtolower($postedFile->getType()))
                {
                    $isAllowed = true;
                }
            }

            if(!$isAllowed)
            {
                $str = sprintf('(%s)', implode(', ', $allowed));
                $msg = str_replace('###', $str, self::RESPONSE_MIME_TYPE_NOT_ALLOWED);

                $this->_setCommunicatorMessage($msg, 'error');
                return false;
            }
        }
        
        $uploadDirectory = $this->getFullPathToUpload();
        
        // verificar el directorio de subida
        if(! is_dir($uploadDirectory))
        {
            $this->_setCommunicatorMessage(self::RESPONSE_UPLOAD_DIRECTORY_NOT_EXIST, 'error');
            return false;
        }
        
        // verificar que tenga permiso de escritura
        if(! is_writable($uploadDirectory))
        {
            $this->_setCommunicatorMessage(self::RESPONSE_UPLOAD_DIRECTORY_NOT_WRITABLE, 'error');
            return false;
        }
        
        if($postedFile instanceof \Com\PostedFile)
        {
            if(! is_uploaded_file($postedFile->getTmpName()))
            {
                $this->_setCommunicatorMessage(self::UNEXPECTED_ERROR, 'error');
                return false;
            }
        }
        
        return true;
    }


    /**
     *
     * @return string
     */
    function getDateDirectory()
    {
        // $year = date('Y');
        // $month = strtolower(date('F'));
        return date('Y-m-d');
    }


    protected function _fileSize($bytes, $unit = '', $decimals = 2)
    {
        $units = array(
            'B' => 0,
            'KB' => 1,
            'MB' => 2,
            'GB' => 3,
            'TB' => 4,
            'PB' => 5,
            'EB' => 6,
            'ZB' => 7,
            'YB' => 8
        );
        
        $value = 0;
        if($bytes > 0)
        {
            // Generate automatic prefix by bytes
            // If wrong prefix given
            if(! array_key_exists($unit, $units))
            {
                $pow = floor(log($bytes) / log(1024));
                $unit = array_search($pow, $units);
            }
            
            // Calculate byte value by prefix
            $value = ($bytes / pow(1024, floor($units[$unit])));
        }
        
        // If decimals is not numeric or decimals is less than 0
        // then set default value
        if(! is_numeric($decimals) || $decimals < 0)
        {
            $decimals = 2;
        }
        
        // Format output
        return sprintf('%.' . $decimals . 'f' . $unit, $value);
    }
}

