<?php

namespace App\Aggregates\Events;

class LoanCreated extends Event
{
    public $totalCents;
    public $currency;
}
