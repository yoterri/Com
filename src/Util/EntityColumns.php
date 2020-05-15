<?php

namespace Com\Util;

use Com\Entity\AbstractEntity;
use Laminas\Db\Sql\Expression;

class EntityColumns 
{

	/**
	 * @var AbstractEntity
	 */
	protected $entity;

	/**
	 * @var string
	 */
	protected $expressionPrefix;

	/**
	 * @var string
	 */
	protected $aliasPrefix;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $useConfigAs;


	/**
	 * @param AbstractEntity $entity
	 */
	function __construct(AbstractEntity $entity = null)
	{
		if($entity)
		{
			$this->setEntity($entity);
		}
	}


	/**
	 * @param AbstractEntity $entity
	 */
	static function getInstance(AbstractEntity $entity = null)
	{
		return new self($entity);
	}


	/**
	 * @param AbstractEntity $entity
	 */
	function setEntity(AbstractEntity $entity)
	{
		$this->entity = $entity;
		return $this;
	}


	/**
	 * @return AbstractEntity
	 */
	function getEntity()
	{
		return $this->entity;
	}


	/**
	 * @param string $aliasPrefix
	 * @param string $expressionPrefix
	 */
	function setPrefix($aliasPrefix, $expressionPrefix = null)
	{
		$this->setAliasPrefix($aliasPrefix);
		$this->setExpresionPrefix($expressionPrefix);
		
		return $this;
	}


	/**
	 * @param string $prefix
	 */
	function setExpresionPrefix($prefix)
	{
		$this->expressionPrefix = $prefix;
		return $this;
	}


	/**
	 * @return string
	 */
	function getExpresionPrefix()
	{
		return $this->expressionPrefix;
	}


	/**
	 * @param string $prefix
	 */
	function setAliasPrefix($prefix)
	{
		$this->aliasPrefix = $prefix;
		return $this;
	}


	/**
	 * @return string
	 */
	function getAliasPrefix()
	{
		return $this->aliasPrefix;
	}



	function setConfig(array $config, $useConfigAs = 'only')
	{
		$this->config = $config;
		$this->useConfigAs = $useConfigAs;
		return $this;
	}


	function extract()
	{
		$prop = array();

		#
		$entity = $this->entity;
		if($entity)
		{
			$prop = $entity->getProperties();
			$useConfigAs = $this->useConfigAs;
			$config = $this->config;

			if($config)
	        {
	            $ret = array();
	            foreach($prop as $val)
	            {
	                if('only' == $useConfigAs)
	                {
	                    if(in_array($val, $config))
	                    {
	                        $ret[] = $val;
	                    }
	                }
	                elseif('exclude' == $useConfigAs)
	                {
	                    if(!in_array($val, $config))
	                    {
	                        $ret[] = $val;
	                    }
	                }
	                else
	                {
	                	$ret[] = $val;
	                }
	            }

	            $prop = $ret;
	        }
		}


		$aliasPrefix = $this->getAliasPrefix();
		$expressionPrefix = $this->getExpresionPrefix();
		if($aliasPrefix)
        {
            $ret = array();

            foreach($prop as $val)
            {
                $alias = "{$aliasPrefix}{$val}";
                if(!empty($expressionPrefix))
                {
                    $ret[$alias] = new Expression("{$expressionPrefix}.{$val}");
                }
                else
                {
                    $ret[] = $alias;
                }
            }

            $prop = $ret;
        }


		return $prop;
	}


	function toArray()
	{
		return $this->extract();
	}

}