<?php
namespace Com;


class Closure
{

	static function extract($obj, $closure)
	{
		# let's get a/execute a protected/private property/method from the object $obj
        # As this is a protected/private property/method we have to 
        # get it using closures
		$get = $closure->bindTo($obj, get_class($obj));
        $result = $get();

        return $result;
	}
}
	
