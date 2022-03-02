<?php

namespace App\Repositories;

use App\Models\EventSource;

class LoanRepository
{
    public function getLoanAggregate($loanId)
    {
        $events = EventSource::loan($loanId)->get();
        if (count($events) == 0) {
            return null;
        }

        $loanAggregate = [
            'id'                    => $loanId,
            'borrower_name'         => null,
            'borrower_email'        => null,
            'borrower_phone'        => null,
            'currency'              => null,
            'total_cents'           => 0,
            'balance_cents'         => 0,
            'repayment_cents'       => 0,
            'daily_interest_rate'   => 0,
            'transactions'          => [],
            'interests'             => []
        ];

        foreach ($events as $event) {
            $data = optional($event->data);
            switch($event->event_type) {
                case 'LoanCreated':
                    $loanAggregate['currency'] = $data['currency'];
                    $loanAggregate['total_cents'] = $data['total_cents'];
                    $loanAggregate['balance_cents'] = $data['total_cents'];
                    $loanAggregate['borrower_name'] = $data['borrower_name'];
                    $loanAggregate['borrower_email'] = $data['borrower_email'];
                    $loanAggregate['borrower_phone'] = $data['borrower_phone'];
                    $loanAggregate['daily_interest_rate'] = $data['daily_interest_rate'] ?? 0;

                    break;
                case 'LoanPerformedDrawdown':
                    $loanAggregate['balance_cents'] = $loanAggregate['balance_cents'] - $data['cents'];
                    $loanAggregate['repayment_cents'] = $loanAggregate['repayment_cents'] + $data['cents'];
                    array_push($loanAggregate['transactions'], [
                        'id'    => $data['id'],
                        'op'    => 'drawdown',
                        'cents' => $data['cents'],
                        'date'  => $data['date']
                    ]);
                    break;
                case 'LoanPerformedRepayment':
                    $loanAggregate['repayment_cents'] = $loanAggregate['repayment_cents'] - $data['cents'];
                    $loanAggregate['balance_cents'] = $loanAggregate['balance_cents'] + $data['cents'];
                    array_push($loanAggregate['transactions'], [
                        'id'    => $data['id'],
                        'op'    => 'repayment',
                        'cents' => $data['cents'],
                        'date'  => $data['date']
                    ]);
                    break;
                default:
            }
        }

        return $loanAggregate;
    }
}

