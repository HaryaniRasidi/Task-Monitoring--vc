<?php
namespace Database\Seeders;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class TaskSeeder extends Seeder {
    public function run(): void {
        if (!app()->environment(['local', 'testing'])) { throw new \RuntimeException('Demo seeding is only allowed locally.'); }
        $password = config('demo.password');
        if (!$password) { throw new \RuntimeException('Set DEMO_PASSWORD in .env before seeding.'); }
        DB::transaction(function () use ($password) {
            $officers = [];
            for ($i = 1; $i <= 17; $i++) {
                $role = $i <= 15 ? 'officer' : 'supervisor'; $number = $i <= 15 ? $i : $i - 15;
                $user = User::firstOrNew(['email' => sprintf('%s%02d@example.test', $role, $number)]);
                if (!$user->exists) { $user->name = sprintf('Demo %s %02d', ucfirst($role), $number); $user->role = $role; $user->password = Hash::make($password); $user->save(); }
                if ($role === 'officer') { $officers[] = $user; }
            }
            $titles = ['Prepare meeting minutes', 'Review draft action plan', 'Submit progress summary'];
            $today = now(Task::TIMEZONE)->startOfDay();
            $offsets = [-10,-9,-8,-7,-6,-5,-4,-3,-2,-1,0,1,2,3,3,4,5,6,7,7,8,10,12,14,16,18,21,24,28,35];
            $openIndex = 0;
            for ($i = 0; $i < 30; $i++) {
                // Reuse the original 10 stable demo titles; reseeding updates rather than duplicates.
                $title = $titles[$i % 3].' (Demo '.sprintf('%02d', $i + 1).')';
                $task = Task::firstOrNew(['title' => $title]);
                $done = in_array($i, [18,19,26,27,28,29], true);
                $ownerIndex = $done ? ($i % 4) + 1 : ($openIndex < 16 ? 0 : (($openIndex - 16) % 4) + 1);
                $task->fill(['title' => $title, 'due_date' => $today->copy()->addDays($offsets[$i])->toDateString(), 'priority' => Task::PRIORITIES[$i % 3], 'status' => $done ? 'done' : ($openIndex % 2 ? 'in progress' : 'to do')]);
                $task->user_id = $officers[$ownerIndex]->id;
                $task->owner = $officers[$ownerIndex]->name;
                $task->description = ''; $task->owner_email = null;
                $task->status_updated_at = (!$done && $openIndex < 12)
                    ? now()->utc() : $today->copy()->startOfWeek()->subDay()->utc();
                $task->save();
                // Activity dates represent demo log dates, never a future day or after the task deadline.
                $anchor = $task->due_date->format('Y-m-d') < $today->toDateString() ? $task->due_date->copy() : $today->copy();
                $activityTitles = ['Demo - Semakan awal', 'Demo - Penyediaan draf', 'Demo - Semakan akhir'];
                $activityNotes = [
                    'Data latihan rekaan: semak keperluan dan skop tugasan.',
                    'Data latihan rekaan: sediakan draf berdasarkan tindakan mesyuarat.',
                    'Data latihan rekaan: semakan akhir dan penyediaan untuk penyerahan.',
                ];
                foreach ($activityTitles as $j => $activityTitle) {
                    $activityStatus = $done ? 'done' : ($task->status === 'to do' ? 'to do' : Task::STATUSES[[2, 1, 0][$j]]);
                    $task->activities()->updateOrCreate(['title' => $activityTitle], [
                        'notes' => $activityNotes[$j],
                        'activity_date' => $anchor->copy()->subDays(2 - $j)->toDateString(),
                        'status' => $activityStatus,
                    ]);
                }
                if (!$done) { $openIndex++; }
            }
        });
        $this->command?->info('Demo dataset: 30 tasks; 10 overdue; 5 due soon (today to +3); 24 open; 6 done; weekly updates 12/24 (50%). 90 demo subactivities with safe dates. Existing non-demo records preserved.');
    }
}