<?php

use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Swow\Coroutine;
use SDPMlab\Anser\Service\Action;
use SDPMlab\Anser\Service\ServiceList;
use Psr\Http\Message\ResponseInterface;
// use SDPMlab\AnserGateway\Orchestrators\v2\CreateOrderOrchestrator;
use SDPMlab\AnserGateway\Orchestrators\v3\CreateOrder;
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/swow.php';

// #### http worker ####
$http_worker = new Worker('http://0.0.0.0:8080');
// 1 processes
$http_worker->count = 1;

$http_worker->onWorkerStart = static function () {
    $swowMiddleware = function (\GuzzleHttp\Psr7\Request $request, array $options){
        $client = new \Swow\Psr7\Client\Client();

        if ($request->getUri()->getPort() === null) {
            $prot = $request->getUri()->getScheme() == 'http'? 80 : 443;
        } else {
            $port = null ;
        }

        // var_dump($request->getUri());
        // try {
            $swowResponse = $client
            ->connect($request->getUri()->getHost(),$prot ?? $request->getUri()->getPort())
            ->setTimeout((int)$options['timeout'] * 1000)
            ->sendRequest($request);
        // } catch (\Swow\Exception $e) {
        //     var_dump($e);
        // }
        
        $response = new \GuzzleHttp\Psr7\Response(
            $swowResponse->getStatusCode(),
            $swowResponse->getHeaders(),
            $swowResponse->getBody()->getContents(),
            $swowResponse->getProtocolVersion(),
            $swowResponse->getReasonPhrase()
        );

        return \GuzzleHttp\Promise\Create::promiseFor($response);
    };
    
    ServiceList::setGlobalHandlerStack($swowMiddleware);

    // ServiceList::addLocalService("product_service",'product_service',80,false);
    // ServiceList::addLocalService("order_service",'order_service',80,false);
    // ServiceList::addLocalService("payment_service",'payment_service',80,false);

    ServiceList::addLocalService("product_service",'140.127.74.162',8081,false);
    ServiceList::addLocalService("order_service",'140.127.74.163',8082,false);
    ServiceList::addLocalService("payment_service",'140.127.74.164',8083,false);

    
};
// Emitted when data received
$http_worker->onMessage = static function ($connection,Request $request) {
    // Coroutine::run(static function () use ($connection, $request) : void {
        
    //     $do = $request->get('test', 'sleep');

    //     $action = (new Action(
    //         "http://127.0.0.1:8081",
    //         "GET",
    //         "/?do=$do"
    //     ))->doneHandler(static function(
    //         ResponseInterface $response,
    //         Action $runtimeAction
    //     ){
    //         $body = $response->getBody()->getContents();
    //         $runtimeAction->setMeaningData($body);
    //     })->setTimeout(5.0);
    //     $data = $action->do()->getMeaningData();

    //     $connection->send('Response' . $data);
    // });

    Coroutine::run(static function () use ($connection) : void {
        // var_dump("I/O Co : ". "[" . Coroutine::getCurrent()->getId() . "]" . date('H:i:s'));
         
        $startTime = date("Y-m-d H:i:s");

        // $product_key    = 1;
        // $product_amount  = 1;
        // $user_key       = 1;

        // $userOrch = new CreateOrderOrchestrator();

        // $result   = $userOrch->build($product_key, $product_amount, $user_key);

        $memberKey = 1;
		$products  = [1,2];
        $createOrder = new CreateOrder();
        $result = $createOrder->build($products, $memberKey);

        $endTime = date("Y-m-d H:i:s");

        $startTimestamp = strtotime($startTime);
        $endTimestamp = strtotime($endTime);

        $timeDiff = $endTimestamp - $startTimestamp;
        var_dump("start : {$startTime} | end : {$endTime}"." use Time : {$timeDiff}");
        $connection->send("start : {$startTime} | end : {$endTime}".PHP_EOL."use Time : {$timeDiff}".PHP_EOL.json_encode($result));
    });
};

$http_worker::$eventLoopClass = \Workerman\Events\Swow::class;
// Run all workers
Worker::runAll();
