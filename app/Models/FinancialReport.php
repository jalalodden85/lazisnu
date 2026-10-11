<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'report_year',
        'report_period',
        'total_inflow',
        'total_outflow',
        'audit_status',
        'file_pdf',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'total_inflow' => 'decimal:2',
            'total_outflow' => 'decimal:2',
            'published_at' => 'date',
        ];
    }
}
