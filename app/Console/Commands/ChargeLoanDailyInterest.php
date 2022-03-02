<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Repositories\LoanRepository;
use App\Models\EventSource;

class ChargeLoanDailyInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charge-daily-interest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge daily interest on unpaid balance for all loans. This command should run on daily basis.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LoanRepository $loanRepository)
    {
        parent::__construct();

        $this->loanRepository = $loanRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $loanAggregates = EventSource::loan()
                            ->groupBy('aggregate_id')
                            ->get('aggregate_id')
                            ->map(function ($event) {
                                return $this->loanRepository->getLoanAggregate($event->aggregate_id);
                            });

        foreach ($loanAggregates as $loanAggregate) {
            $drawdowns = [];
            $repayments = [];
            $unpaidBalancesWithDates = [];

            foreach($loanAggregate['transactions'] as $txn) {
                if ($txn['op'] === 'drawdown') {
                    array_push($drawdowns, $txn);

                    $txnDate = Carbon::parse($txn['date'])->toDateString();
                    if (!isset($unpaidBalancesWithDates[$txnDate])) {
                        $unpaidBalancesWithDates[$txnDate] = 0;
                    }

                    $unpaidBalancesWithDates[$txnDate] = $unpaidBalancesWithDates[$txnDate] + $txn['cents'];

                    continue;
                }
                array_push($repayments, $txn);
            }

            foreach ($unpaidBalancesWithDates as $date => $unpaidCents) {
                // we use repayment to offset the unpaid balance
                foreach($repayments as $repayment) {
                    $repaymentDate = Carbon::parse($repayment['date'])->toDateString();
                    $unpaidDate = Carbon::parse($date);

                    if ($unpaidDate->gt($repaymentDate)) {
                        continue;
                    }

                    if ($unpaidCents > $repayment['cents']) {
                        $unpaidBalancesWithDates[$date] = $unpaidCents - $repayment['cents'];
                        $repayment['cents'] = 0;
                    } else {
                        $repayment['cents'] = $repayment['cents'] - $unpaidCents;
                        unset($unpaidBalancesWithDates[$date]);
                    }
                }
            }


            if (count($unpaidBalancesWithDates) > 0) {
                $interestSum = 0;
                foreach ($unpaidBalancesWithDates as $date => $unpaidBalance) {
                    $drawdownDate = Carbon::parse($date);
                    $days = $drawdownDate->diffInDays(now());
                    $interestSum += $unpaidBalance * $loanAggregate['daily_interest_rate'] * $days;
                }

                $this->info(json_encode($unpaidBalancesWithDates));
                $this->info($interestSum);
            }
        }
    }
}
