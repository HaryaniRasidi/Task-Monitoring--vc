<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    protected $fillable = ['title', 'notes', 'status', 'activity_date'];

    protected function casts(): array
    {
        return ['activity_date' => 'date'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
