<?php
namespace Com;


class Stdout
{
	
	static function write($dots, $msg)
	{
		static $isCli = null;

		if(is_null($isCli))
		{
			$isCli = (php_sapi_name() === 'cli');
		}

		if($isCli)
		{
			$dots = abs((int)$dots);
			if($dots)
			{
				$msg = str_repeat('.', $dots) . " $msg";
			}
		    
		    fwrite(STDOUT, $msg);
		}
	}


	static function writenl($dots, $msg, $nl = 1)
	{
		$nl = abs((int)$nl);
		if(0 == $nl)
		{
			$nl = 1;
		}

		$msg .= str_repeat(PHP_EOL, $nl);
		self::write($dots, $msg);
	}

	
	static function nl($nl = 1)
	{
		$nl = abs((int)$nl);
		if(0 == $nl)
		{
			$nl = 1;
		}

		$msg = str_repeat(PHP_EOL, $nl);
		self::write(0, $msg);
	}	
}
