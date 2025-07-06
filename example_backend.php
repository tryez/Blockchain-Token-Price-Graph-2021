<?php

header('Content-Type: application/json; charset=utf-8');

include "config/index.php";
include "UznaTokenGraph.php";


if($_POST['time']){

    // sleep(20);

    $svg = UznaTokenGraph::getSvgFromRequest($_POST);

    echo json_encode([
        'success' => true,
        'data' => $svg
    ]);
}




