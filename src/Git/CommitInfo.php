<?php
namespace Com\Git;
use Com;
use Com\Communicator;
use Com\Zend\Date;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CommitInfo
{

	protected $gitPath;
	protected $gitLogInfo;
	protected $communicator;
	protected $remote;
	protected $currBranch;

	    

	/**
	 * @param string $gitPath
	 */
	function __construct($gitPath = null)
	{
		$this->communicator = new Communicator();

		if(!empty($gitPath))
		{
			$this->setGitPath($gitPath);
		}
	}


	/**
	 * @return Com\Communicator
	 */
	function getCommunicator()
	{
		return $this->communicator;
	}


	/**
	 * @return bool
	 */
	function isSuccess()
	{
		return $this->getCommunicator()->isSuccess();
	}


	/**
	 * @param string $gitPath
	 * @return CommitInfo
	 */
	function setGitPath($gitPath)
	{
		$this->communicator->clearErrors();

		$setPath = true;
		if(!is_dir($gitPath))
		{
			$setPath = false;
			$this->communicator->addError("Provided git path '$gitPath' is not a dir");
		}

		if(!is_readable($gitPath))
		{
			$setPath = false;
			$this->communicator->addError("Provided git path '$gitPath' is not readable");
		}

		if($setPath)
		{
			$this->gitPath = $gitPath;
		}
		
		#
		$this->gitLogInfo = null;
		
		$this->remote = null;
		$this->currBranch = null;

		return $this;
	}


	/**
	 * @param bool $short
	 * @return string
	 */
	function getCommitHash($short = true)
	{
		$this->_gitLogInfo();
		if(!$this->gitLogInfo)
		{
			return 'Unknown';
		}

        $text = $this->gitLogInfo[0];
        $exploded = explode(' ', $text);
        $hash = $exploded[1];
        if($short)
        {
            $hash = substr($hash, 0, 7) ;
        }

        return $hash;	
	}


	/**
	 * @return string
	 */
	function getCommitMessage()
	{
		$this->_gitLogInfo();
		if(!$this->gitLogInfo)
		{
			return 'Unknown';
		}

		$msg = '';
        foreach($this->gitLogInfo as $key => $item)
        {
            if($key <= 2)
            {
                continue;
            }

            $msg .= nl2br($item);
        }

        return $msg;
	}

	/**
	 * @return string
	 */
	function getCommitAuthor()
    {
    	$this->_gitLogInfo();
		if(!$this->gitLogInfo)
		{
			return 'Unknown';
		}

        $exploded = explode(' ', $this->gitLogInfo[1]);
        unset($exploded[0]);

        $val = implode(' ', $exploded);

        return $val;
    }
	

    /**
	 * @param string $format
	 * @return string
	 */
	function getCommitDate($format = null)
	{
		$this->_gitLogInfo();
		if(!$this->gitLogInfo)
		{
			return 'Unknown';
		}

		$exploded = explode(' ', $this->gitLogInfo[2]);
        unset($exploded[0]);

        $strDate = implode(' ', $exploded);

        #Wed Jan 3 01:20:46 2018 +0000
        $part = 'EEE MMM d HH:mm:ss y X';
        if(empty($format))
        	$format = 'MMM dSS, y - WW';

        $oDate = new Date($strDate, $part);
        $strDateFormatted = $oDate->get($format);

        return $strDateFormatted;
	}


	/**
	 * @return string
	 */
	function getRemote()
	{
		if(empty($this->remote))
		{
			$this->remote = 'Unknown';

			$process = $this->_createGitProcess(array('config', '--get', 'remote.origin.url'));

			try
	        {
	            $process->run();
	            if($process->isSuccessful())
	            {
	                $exploded = explode(':', $process->getOutput());
	                $this->remote = $exploded[1];
	            }
	            else
	            {
	                $commandLine = $process->getCommandLine();

					$error = sprintf('Command: %s<br>Error: %s (%s) - %s', $commandLine, $process->getExitCodeText(), $process->getExitCode(), $process->getOutput());
					$this->communicator->addError($error);
	            }
	        }
	        catch(\exception $e)
	        {
	            $this->communicator->setException($e);
	        }
		}

        return $this->remote;
	}


	/**
	 * @return string
	 */
	function getCurrBranch()
	{
		if(empty($this->currBranch))
		{
			$this->currBranch = 'Unknown';

			$process = $this->_createGitProcess(array('rev-parse', '--abbrev-ref', 'HEAD'));

			try
	        {
	            $process->run();
	            if($process->isSuccessful())
	            {
	                $this->currBranch = $process->getOutput();
	            }
	            else
	            {
	                $commandLine = $process->getCommandLine();

					$error = sprintf('Command: %s<br>Error: %s (%s) - %s', $commandLine, $process->getExitCodeText(), $process->getExitCode(), $process->getOutput());
					$this->communicator->addError($error);
	            }
	        }
	        catch(\exception $e)
	        {
	            $this->communicator->setException($e);
	        }
		}

        return $this->currBranch;
	}


	protected function _gitLogInfo()
	{
		if(!is_null($this->gitLogInfo))
		{
			return ;
		}

		#
		$process = $this->_createGitProcess(array('log', '-1'));
		try
		{
			$this->gitLogInfo = array();
			$process->run();
			if($process->isSuccessful())
			{
				$this->gitLogInfo = explode(PHP_EOL, $process->getOutput());
			}
			else
			{
				$commandLine = $process->getCommandLine();

				$error = sprintf('Command: %s<br>Error: %s (%s) - %s', $commandLine, $process->getExitCodeText(), $process->getExitCode(), $process->getOutput());
				$this->communicator->addError($error);
			}
		}
		catch(\exception $e)
        {
            $this->communicator->setException($e);
        }
	}


	/**
	 * @param array $params
	 * @return Process
	 */
	protected function _createGitProcess(array $params = array())
    {
        $command = array();
        $command[] = 'git';
		$command[] = "--git-dir={$this->gitPath}";

        foreach($params as $value)
        {
            $command[] = $value;
        }

        $process = new Process($command);
        return $process;
    }
}