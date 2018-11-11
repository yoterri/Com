<?php

namespace Com\InputFilter;
use Interop\Container\ContainerInterface;
use Com\ContainerAwareInterface;
use Zend\InputFilter\InputFilter;
use Com\LazyLoadInterface;

abstract class AbstractInputFilter extends InputFilter implements ContainerAwareInterface, LazyLoadInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    
    /**
     * @return ContainerInterface
     */
    function getContainer()
    {
        return $this->container;
    }


    function setData($data)
    {
        if(is_object($data) && method_exists($data, 'toArray'))
        {
            $data = $data->toArray();
        }
        
        parent::setData($data);

        return $this;
    }


    /**
     * Translate a message using the given text domain and locale
     *
     * @param string $message
     * @param string $textDomain
     * @param string $locale
     * @return string
     */
    function _($message, $textDomain = 'default', $locale = null)
    {
        $sm = $this->getContainer();

        if($sm->has('translator'))
        {
            $message = $sm->get('translator')->translate($message, $textDomain, $locale);
        }

        return $message;
    }


    function build()
    {
        $filters = $this->getFilters();
        
        #
        foreach($filters as $filter)
        {
            $this->add($filter);
        }
        
        return $this;
    }


    /**
     * Return a list of filtered values
     *
     * @return array
     */
    public function getValues($onlyProvided=false)
    {
        $values = parent::getValues();

        if(!$onlyProvided)
        {
            return $values;
        }

        foreach($values as $key => $value)
        {
            if(!isset($this->data[$key]))
            {
                unset($values[$key]);
            }
        }

        return $values;
    }


    abstract function getFilters();
}