
<?php
/**
 *
 * @author <yoterri@ideasti.com>
 * @copyright 2014
 */
namespace Com;

class PostedFile
{

    /**
     *
     * @var string
     */
    protected $_name = null;

    /**
     *
     * @var string
     */
    protected $_type = null;

    /**
     *
     * @var int
     */
    protected $_size = null;

    /**
     *
     * @var string
     */
    protected $_tmpName = null;

    /**
     *
     * @var int
     */
    protected $_error = null;

    /**
     *
     * @var string
     */
    protected $_extension;


    /**
     *
     * @param string|array $name - $_FILES[$name]['name'] The original name of the file in the client's computer
     * @param string $type - $_FILES[$type]['type'] the file's mime type
     * @param int $size - $_FILES[$size]['size']
     * @param string $tmpName - $_FILES[$tmpName]['tmp_name']
     * @param int $error - $_FILES[$error]['error']
     */
    function __construct($name, $type = null, $size = null, $tmpName = null, $error = null)
    {
        if(is_array($name))
        {
            $tmp = $name;
            
            $name = isset($tmp['name']) ? $tmp['name'] : '';
            if($name)
            {
                $type = $tmp['type'];
                $tmpName = $tmp['tmp_name'];
                $error = $tmp['error'];
                $size = $tmp['size'];
            }
            else
            {
                $type = null;
                $tmpName = null;
                $error = 4;
                $size = 0;
            }
        }
        
        $this->_name = (string)$name;
        $this->_type = (string)$type;
        $this->_size = (int)$size;
        $this->_tmpName = (string)$tmpName;
        $this->_error = (int)$error;
        $this->_extension = '';
        
        if(stripos($this->_name, '.') !== false)
        {
            $explode = explode('.', $this->_name);
            $ext = end($explode);
            $this->_extension = strtolower($ext);
        }
    }


    /**
     *
     * @param array $extensions array('jpg', 'png', 'gif')
     * @return bool
     */
    function hasExtension(array $extensions)
    {
        $pattern = '';
        foreach($extensions as $value)
            $pattern .= '(\.' . $value . ')$|';
        
        $pattern = substr($pattern, 0, strlen($pattern) - 1);
        return preg_match("/$pattern/i", $this->getName());
    }


    /**
     *
     * @return boolean
     */
    function hasFile()
    {
        return ($this->getError() != 4);
    }


    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }


    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }


    /**
     *
     * @return int
     */
    public function getSize()
    {
        return $this->_size;
    }


    /**
     *
     * @return string
     */
    public function getTmpName()
    {
        return $this->_tmpName;
    }


    /**
     *
     * @return int
     */
    public function getError()
    {
        return $this->_error;
    }


    /**
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->_extension;
    }
}