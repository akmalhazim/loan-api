<?php

namespace App\Aggregates;

use App\Aggregates\Events\Event;

abstract class Aggregate
{
    private $events;

    public function appendEvent(Event $event)
    {
        array_push($events, $event);
    }

    abstract function applyEvent(Event $event);

    abstract function handleCommand($cmd);
}
