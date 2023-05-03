<?php

namespace App\Interfaces;

interface IWorker {
    public function startWork();
    public function stopWork();
}