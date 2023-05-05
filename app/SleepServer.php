<?php

use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Swow\Coroutine;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/swow.php';

// #### http worker ####
$http_worker = new Worker('http://0.0.0.0:8081');
// 1 processes
$http_worker->count = 1;
// Emitted when data received
$http_worker->onMessage = static function ($connection,Request $request) {
    Coroutine::run(static function () use ($connection, $request) : void {
        $start = date('Y-m-d H:i:s');
        $do = $request->get('do');
        if($do == 'sleep'){
            sleep(3);
        }
        $connection->send($connection->worker->id . ' - ' .Coroutine::getCurrent()->getId() . ', start: ' . $start . ', now: ' . date('Y-m-d H:i:s'));
    });
};

$http_worker::$eventLoopClass = \Workerman\Events\Swow::class;
// Run all workers
Worker::runAll();
