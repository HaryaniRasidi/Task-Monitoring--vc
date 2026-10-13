<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Task extends Model {
    public const TIMEZONE = 'Asia/Kuala_Lumpur';
    public const OPEN_STATUSES = ['to do', 'in progress'];
    public const DUE_SOON_DAYS = 3;
    public static function today(): string { return now(self::TIMEZONE)->toDateString(); }
    public static function reminderDate(): string { return now(self::TIMEZONE)->addDays(self::DUE_SOON_DAYS)->toDateString(); }
    public function isOpen(): bool { return in_array($this->status, self::OPEN_STATUSES, true); }
    public function isOverdue(): bool { return $this->isOpen() && $this->due_date->format('Y-m-d') < self::today(); }
    public function isDueSoon(): bool { $date = $this->due_date->format('Y-m-d'); return $this->isOpen() && $date >= self::today() && $date <= self::reminderDate(); }
    public function scopeOpen(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder { return $query->whereIn('status', self::OPEN_STATUSES); }
    public function scopeOverdue(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder { return $query->open()->whereDate('due_date', '<', self::today()); }
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const STATUSES = ['to do', 'in progress', 'done'];
    protected $fillable = ['title', 'due_date', 'priority', 'status'];
    protected function casts(): array { return ['due_date' => 'date', 'status_updated_at' => 'datetime']; }
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(TaskActivity::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
