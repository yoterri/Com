<?php

namespace Com\Entity\Priv;

use Com\Entity\AbstractEntity;
use Com\LazyLoadInterface;

class Role extends AbstractEntity implements LazyLoadInterface
{
	protected $properties = array(
        'id'
        ,'name'
        ,'description'
    );
}