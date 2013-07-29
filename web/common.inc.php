<?php

$WHISPER_DIR = "/var/lib/carbon/whisper/monitoring/";
$GRAPHITE_BASE_URL = "http://graphite.mysite.com";
$GRAPH_DEFAULT_SIZE = "800x600";

function getvar_default($a, $k, $default)
{
    return (array_key_exists($k, $a) ? $a[$k] : $default);
}

function get_queues()
{
    global $WHISPER_DIR;

    $queues = array();
    if($handle = opendir($WHISPER_DIR)) {
        while(($queue = readdir($handle)) !== false) {
            if($queue != '.' && $queue != '..')
                $queues[] = $queue;
        }
        closedir($handle);
    }
    sort($queues);
    return $queues;
}

?>
