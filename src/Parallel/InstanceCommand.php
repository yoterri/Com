<?php

namespace Com\Parallel;

class InstanceCommand extends AbstractCommand
{

    protected $rowIdentifier = 'url';
    protected $logPath = '/tmp/log.log';


    function process($row)
    {
        $url = $row['url'];
        $content = file_get_contents($url);

        #
        $message = "$url - len: " . strlen($url);
        $this->log($message);
    }


    function getRowset()
    {
        $rowset = [
            ['url' => 'https://eldeber.com.bo'],
            ['url' => 'https://www.trabajopolis.bo'],
            ['url' => 'http://eju.tv'],
            ['url' => 'https://www.paginasiete.bo'],
        ];

        return $rowset;
    }


    function onProcessed($row, $instanceRow, $itemRow)
    {
        $url = $row['url'];

        #
        $message = "$url - processed";
        $this->log($message);
    }
}