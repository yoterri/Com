<?php
namespace Com\Control;
use Com\LazyLoadInterface;
use Zend\Db\ResultSet\AbstractResultSet;

class AuditLog extends AbstractControl implements LazyLoadInterface
{

	function save($title, $userId, $detail = null)
	{
		$sm = $this->getContainer();

		$in = array();
		$in['created_on'] = date('Y-m-d H:i:s');
		$in['title'] = $title;
		$in['user_id'] = $userId;

		$in['url'] = '';
		if(isset($_SERVER['REQUEST_URI']))
        {
            $in['url'] = $_SERVER['REQUEST_URI'];
        }

        $in['ip_address'] = '';
		if(isset($_SERVER['REMOTE_ADDR']))
        {
            $in['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }

        if(!empty($detail))
        {
        	$in['detail'] = $detail;
        }

		$dbAuditLog = $sm->get('Com\Db\AuditLog');

		$dbAuditLog->doInsert($in);
	}
}