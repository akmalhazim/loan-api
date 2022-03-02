<?php

namespace App\Http\Controllers;

use App\Repositories\LoanRepository;
use App\Models\EventSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoanController extends Controller
{
    private $loanRepository;

    public function __construct(LoanRepository $loanRepo)
    {
        $this->loanRepository = $loanRepo;
    }

    public function index()
    {
        $loanAggregates = EventSource::loan()
                                ->groupBy('aggregate_id')
                                ->get('aggregate_id')
                                ->map(function ($event) {
                                    return $this->loanRepository->getLoanAggregate($event->aggregate_id);
                                });

        return response()->json($loanAggregates);
    }

    public function create(Request $request)
    {
        $request->validate([
            'currency'       => 'required|string|in:myr',
            'total_cents'    => 'required|int|min:0',
            'borrower_name'  => 'required|string',
            'borrower_email' => 'required|email',
            'borrower_phone' => 'required|string'
        ]);

        $loanId = $this->uuid();

        EventSource::create([
            'aggregate_type' => 'Loan',
            'aggregate_id'   => $loanId,
            'event_type'     => 'LoanCreated',
            'data'           => array_merge($request->only([
                'currency', 'total_cents', 'borrower_name', 'borrower_email', 'borrower_phone'
            ]), [
                'daily_interest_rate' => config('app.default_daily_interest_rate')
            ])
        ]);

        $loanAggregate = $this->loanRepository->getLoanAggregate($loanId);

        return response()->json($loanAggregate, 201);
    }

    public function show($loanId)
    {
        $loanAggregate = $this->loanRepository->getLoanAggregate($loanId);
        if (!$loanAggregate) {
            abort(404, 'Loan not found');
        }

        return response()->json($loanAggregate);
    }

    public function createTransaction($loanId, Request $request)
    {
        $request->validate([
            'op'    => 'required|string|in:drawdown,repayment',
            'cents' => 'required|int',
        ]);

        $loanAggregate = $this->loanRepository->getLoanAggregate($loanId);
        if (!$loanAggregate) {
            abort(404, 'Loan not found');
        }

        if ($request->op === 'drawdown') {
            if ($request->cents > $loanAggregate['balance_cents']) {
                abort(400, 'Drawdown exceeds balance limit');
            }

            EventSource::create([
                'aggregate_type' => 'Loan',
                'aggregate_id'   => $loanId,
                'event_type'     => 'LoanPerformedDrawdown',
                'data'           => array_merge($request->only([
                    'cents'
                ]), [
                    'id' => $this->uuid(),
                    'date' => now(),
                ])
            ]);
        } else {
            if ($request->cents > $loanAggregate['repayment_cents']) {
                abort(400, 'Repayment must be within repayment balance');
            }

            EventSource::create([
                'aggregate_type' => 'Loan',
                'aggregate_id'   => $loanId,
                'event_type'     => 'LoanPerformedRepayment',
                'data'           => array_merge($request->only([
                    'cents'
                ]), [
                    'id' => $this->uuid(),
                    'date' => now(),
                ])
            ]);
        }

        return response()->json($this->loanRepository->getLoanAggregate($loanId), 201);
    }

    private function uuid()
    {
        return (string) Str::uuid();
    }
}
