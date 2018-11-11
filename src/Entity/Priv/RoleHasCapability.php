<?php

namespace Com\Entity\Priv;

use Com\Entity\AbstractEntity;
use Com\LazyLoadInterface;

class RoleHasCapability extends AbstractEntity implements LazyLoadInterface
{
	protected $properties = array(
        'id'
        ,'role_id'
        ,'capability_id'
    );
}