<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'aggregate_id', 'aggregate_type', 'event_type', 'data'
    ];

    protected $casts = [
        'data'  => 'json',
    ];

    public function scopeLoan($query, $loanId = null)
    {
        return $query
            ->where('aggregate_type', 'Loan')
            ->where(function ($query) use ($loanId) {
                if ($loanId) {
                    return $query->where('aggregate_id', $loanId);
                }
            });
    }
}
