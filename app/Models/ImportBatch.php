<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'user_id',
        'source_type',
        'source_path',
        'total_detected',
        'imported_count',
        'skipped_count',
        'duplicate_count',
        'failed_count',
        'status',
        'summary_report',
    ];

    protected $casts = [
        'total_detected' => 'integer',
        'imported_count' => 'integer',
        'skipped_count' => 'integer',
        'duplicate_count' => 'integer',
        'failed_count' => 'integer',
        'summary_report' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
