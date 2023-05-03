<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use TorMorten\Eventy\Facades\Events as Eventy;

class ProcessIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // event metadata
    protected $data;

    public function __construct($metadata)
    {
        $this->data = $metadata;
    }

    public function handle()
    {
        list($slug, $not_used) = explode('-', $this->data['int_name']);
        Eventy::action("jobs.{$slug}.process", $this->data);
    }
}


