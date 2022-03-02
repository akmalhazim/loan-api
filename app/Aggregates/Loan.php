<?php

namespace App\Aggregates;

class Loan extends Aggregate
{
    public $id;
    public $borrowerName;
    public $borrowerEmail;
    public $borrowerPhone;
    public $currency;
    public $totalCents;
    public $balanceCents;
    public $repaymentBalance;

    public function applyEvent(Event $event)
    {
    }

    public function handleCommand($cmd)
    {
    }
}
