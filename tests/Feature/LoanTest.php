<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoanTest extends TestCase
{
    public function test_can_get_loans()
    {
        $response = $this->get('/api/loans');

        $response->assertStatus(200);
    }

    public function test_can_create_loan_with_valid_data()
    {
        $response = $this->postJson('/api/loans', [
            'currency'       => 'myr',
            'total_cents'    => 1000000,
            'borrower_name'  => 'PHPUnit Test',
            'borrower_email' => 'phpunit@example.com',
            'borrower_phone' => '0123456789'
        ]);

        $response
                ->assertStatus(201)
                ->assertJson([
                    'currency'              => 'myr',
                    'total_cents'           => 1000000,
                    'balance_cents'         => 1000000,
                    'borrower_name'         => 'PHPUnit Test',
                    'borrower_email'        => 'phpunit@example.com',
                    'borrower_phone'        => '0123456789',
                    'daily_interest_rate'   => 0.01,
                    'transactions'          => []
                ]);
    }

    public function test_cant_create_loan_with_invalid_data()
    {
        $response = $this->postJson('/api/loans', [
            'currency'       => 'usd',
        ]);

        $response
                ->assertStatus(422)
                ->assertJson([
                    'errors' => [
                        'currency' => [
                            'The selected currency is invalid.'
                        ],
                        'total_cents' => [
                            'The total cents field is required.'
                        ],
                        'borrower_name' => [
                            'The borrower name field is required.'
                        ],
                        'borrower_email' => [
                            'The borrower email field is required.'
                        ],
                        'borrower_phone' => [
                            'The borrower phone field is required.'
                        ]
                    ]
                ]);
    }

    public function test_can_perform_drawdown_with_valid_data()
    {
        $response = $this->postJson('/api/loans', [
            'currency'       => 'myr',
            'total_cents'    => 1000000,
            'borrower_name'  => 'PHPUnit Test',
            'borrower_email' => 'phpunit@example.com',
            'borrower_phone' => '0123456789'
        ]);

        $loanAggregate = $response->decodeResponseJson();

        $response = $this->postJson('/api/loans/'.$loanAggregate['id'].'/transactions', [
            'op'    => 'drawdown',
            'cents' => 750000
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'total_cents'       => 1000000,
                'balance_cents'     => 250000,
                'repayment_cents'   => 750000,
            ]);
    }

    public function test_can_perform_repayment_with_valid_data()
    {
        $response = $this->postJson('/api/loans', [
            'currency'       => 'myr',
            'total_cents'    => 1000000,
            'borrower_name'  => 'PHPUnit Test',
            'borrower_email' => 'phpunit@example.com',
            'borrower_phone' => '0123456789'
        ]);

        $loanAggregate = $response->decodeResponseJson();

        $response = $this->postJson('/api/loans/'.$loanAggregate['id'].'/transactions', [
            'op'    => 'drawdown',
            'cents' => 750000
        ]);

        $response = $this->postJson('/api/loans/'.$loanAggregate['id'].'/transactions', [
            'op'    => 'repayment',
            'cents' => 250000
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'total_cents'       => 1000000,
                'balance_cents'     => 500000,
                'repayment_cents'   => 500000,
            ]);
    }

    public function test_can_view_loan()
    {
        $response = $this->postJson('/api/loans', [
            'currency'       => 'myr',
            'total_cents'    => 1000000,
            'borrower_name'  => 'PHPUnit Test',
            'borrower_email' => 'phpunit@example.com',
            'borrower_phone' => '0123456789'
        ]);
        
        $response->assertStatus(201);

        $loanId = $response->decodeResponseJson()['id'];

        $response = $this->get('/api/loans/'.$loanId);
        
        $response
                ->assertStatus(200)
                ->assertJson([
                    'id' => $loanId,
                    'total_cents'    => 1000000,
                    'borrower_name'  => 'PHPUnit Test',
                    'borrower_email' => 'phpunit@example.com',
                    'borrower_phone' => '0123456789'
                ]);
    }
}
