<?php

namespace Com\Entity\Priv;

use Com\Entity\AbstractEntity;
use Com\LazyLoadInterface;

class Capability extends AbstractEntity implements LazyLoadInterface
{
	protected $properties = array(
        'id'
        ,'name'
        ,'category_id'
        ,'description'
    );
}