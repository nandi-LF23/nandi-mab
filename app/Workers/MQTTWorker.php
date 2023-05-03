<?php

namespace App\Workers;

use App\Interfaces\IWorker;
use App\Models\Connection;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class MQTTWorker implements IWorker {

    protected $conn; // connection
    protected $mqtt; // dependency

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function startWork(){

        $config = json_decode($this->conn->config, true);

        if(empty($config['server']))   { Log::debug("MQTTWorker: missing 'server' config item.");   return -1; }
        if(empty($config['port']))     { Log::debug("MQTTWorker: missing 'port' config item.");     return -1; }
        if(empty($config['username'])) { Log::debug("MQTTWorker: missing 'username' config item."); return -1; }
        if(empty($config['password'])) { Log::debug("MQTTWorker: missing 'password' config item."); return -1; }
        if(empty($config['topic']))    { Log::debug("MQTTWorker: missing 'topic' config item.");    return -1; }

        $server    = $config['server'];
        $port      = $config['port'];
        $username  = $config['username'];
        $password  = $config['password'];
        $topic     = $config['topic'];
        $client_id = $this->conn->name . ' (' . $this->conn->id . ')';

        try {

            $connSettings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password);

            $this->mqtt = new MqttClient($server, $port, $client_id);
            $this->mqtt->connect(
                $connSettings,
                false // clean session flag
            );

            $this->conn->started = (new \DateTime())->format('Y-m-d H:i:s');
            $this->conn->status = 'Connected';
            $this->conn->save();

            Log::debug("Worker Started");

            $this->mqtt->subscribe($topic, \Closure::fromCallable([ $this, 'doWork' ]), MqttClient::QOS_EXACTLY_ONCE);
            $this->mqtt->loop(true);
            $this->mqtt->disconnect();

            Log::debug("Worker Stopped");

            $this->conn->status = 'Disconnected';
            $this->conn->started = NULL;
            $this->conn->save();

        } catch (\Exception $e){
            Log::debug("MQTTWorker: " . $e->getMessage());
        }

    }

    public function stopWork(){
        $this->mqtt->interrupt();
    }

    public function doWork(string $topic, string $message, bool $retained){
        Log::debug("Received message on topic [%s]: %s\n", $topic, $message);
    }

}