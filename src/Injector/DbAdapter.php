<?php

namespace Com\Injector;

use Interop\Container\ContainerInterface;

class DbAdapter
{

    public function __invoke(ContainerInterface $container, $instance)
    {
        #Laminas\Db\Adapter\AdapterAwareInterface
        if(method_exists($instance, 'getAdpaterKey'))
        {
            $adapterKey = $instance->getAdpaterKey();
            if($adapterKey)
            {
                $adapter = $container->get($adapterKey);
                $instance->setDbAdapter($adapter);
            }
            else
            {
                $this->_def($container, $instance);
            }
        }
        else
        {
            $this->_def($container, $instance);
        }
    }
    

    protected function _def($container, $instance)
    {
        $adapter = $container->get('adapter');
        $instance->setDbAdapter($adapter);
    }
}