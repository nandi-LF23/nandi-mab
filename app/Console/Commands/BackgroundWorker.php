<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\SignalableCommandInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Models\Connection;
use App\Workers\MQTTWorker;

class BackgroundWorker extends Command implements SignalableCommandInterface
{
    protected $signature = 'bgworker {conn_id : The MAB Connection ID}';
    protected $description = 'MAB: Start a Background Worker';
    protected $conn_id;
    protected $worker;
    protected $running = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->conn_id = $this->argument('conn_id');

        $conn = Connection::where('id', $this->conn_id)->first();
        if(!$conn){ Log::debug('Worker: Invalid Connection ID'); return -1; }

        if($conn->type == 'MQTT'){
            $this->worker = new MQTTWorker($conn);
            $this->worker->startWork();
        }

        return 0;
    }

    public function getSubscribedSignals(): array
    {
        return [ SIGINT, SIGTERM ];
    }
 
    public function handleSignal(int $signal): void
    {
        if (in_array($signal, [ SIGINT, SIGTERM ])) {

            $this->worker->stopWork();

        }
    }
}
