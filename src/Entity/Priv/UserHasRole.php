<?php

namespace Com\Entity\Priv;

use Com\Entity\AbstractEntity;
use Com\LazyLoadInterface;

class UserHasRole extends AbstractEntity implements LazyLoadInterface
{
	protected $properties = array(
        'id'
        ,'user_id'
        ,'role_id'
    );
}