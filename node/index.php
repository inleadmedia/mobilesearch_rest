<?php

if (isset($_SERVER['REQUEST_URI'])) {
    $uri = explode('/', $_SERVER['REQUEST_URI']);
    $project = $uri[2];
    switch ($project) {
        case "home":
            $port = '9045';
            break;
        case "library":
            $port = '9046';
            break;
        case "couch":
            $port = '9047';
            break;
    }
    array_shift($uri);
    array_shift($uri);
    array_shift($uri);
    $node_uri = implode('/', $uri);
}
if (!isset($port)) {
    exit;
}

// build the individual requests, but do not execute them
$node_1 = curl_init('http://146.192.168.164:'.$port.'/'.$node_uri);
$node_2 = curl_init('http://146.192.168.165:'.$port.'/'.$node_uri);
$node_3 = curl_init('http://146.192.168.165:'.$port.'/'.$node_uri);
curl_setopt($node_1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($node_2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($node_3, CURLOPT_RETURNTRANSFER, true);

// build the multi-curl handle, adding both $ch
$mh = curl_multi_init();
curl_multi_add_handle($mh, $node_1);
curl_multi_add_handle($mh, $node_2);
curl_multi_add_handle($mh, $node_3);

// execute all queries simultaneously, and continue when all are complete
$running = null;
do {
    curl_multi_exec($mh, $running);
} while ($running);

//close the handles
curl_multi_remove_handle($mh, $node_1);
curl_multi_remove_handle($mh, $node_2);
curl_multi_remove_handle($mh, $node_3);
curl_multi_close($mh);

// all of our requests are done, we can now access the results
$response_1 = curl_multi_getcontent($node_1);
$response_2 = curl_multi_getcontent($node_2);
$response_3 = curl_multi_getcontent($node_3);

// output results
echo "Node1: " . $response_1 . "<br>\n";
echo "Node2: " . $response_2 . "<br>\n";
echo "Node3: " . $response_3 . "<br>\n";
