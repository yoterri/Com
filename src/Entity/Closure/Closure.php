<?php

namespace Com\Entity\Closure;

use Com\Entity\AbstractEntity;
use Com\LazyLoadInterface;

class Closure extends AbstractEntity implements LazyLoadInterface
{
	protected $properties = array(
        'id'
        ,'parent_id'
        ,'child_id'
        ,'depth'
        ,'group_id'
    );
}