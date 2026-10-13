<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaskReminder extends Model {
    protected $fillable = ['task_id', 'due_date', 'status', 'sent_at'];
    protected function casts(): array { return ['sent_at' => 'datetime']; }
}
