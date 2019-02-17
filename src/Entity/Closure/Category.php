<?php

namespace Com\Entity\Closure;

use Com\Entity\AbstractEntity;
use Com\LazyLoadInterface;

class Node extends AbstractEntity implements LazyLoadInterface
{
	protected $properties = array(
        'id'
        ,'name'
        ,'description'
    );
}