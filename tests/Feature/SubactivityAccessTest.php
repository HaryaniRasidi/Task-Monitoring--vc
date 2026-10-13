<?php
namespace Tests\Feature;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SubactivityAccessTest extends TestCase {
    use RefreshDatabase;
    public function test_owner_manages_subactivities_and_supervisor_only_views(): void {
        $owner = User::factory()->create(); $other = User::factory()->create();
        $supervisor = User::factory()->create(); $supervisor->role = 'supervisor'; $supervisor->save();
        $task = new Task(['title'=>'Demo task','due_date'=>Task::today(),'priority'=>'low','status'=>'to do']);
        $task->owner=$owner->name; $task->user_id=$owner->id; $task->description=''; $task->save();
        $url = route('tasks.activities.store',$task);
        $data=['title'=>'<b>Demo subactivity</b>','notes'=>'Demo notes','activity_date'=>'2026-10-13','status'=>'in progress'];
        $this->actingAs($owner)->post($url,$data)->assertRedirect(route('tasks.show',$task).'#subactivities');
        $activity=$task->activities()->firstOrFail();
        $this->get(route('tasks.show',$task))->assertOk()->assertSee('Demo notes')->assertSee($data['title'])->assertDontSee($data['title'],false);
        $this->get(route('tasks.activities.edit',[$task,$activity]))->assertOk();
        $data['status']='done';
        $this->patch(route('tasks.activities.update',[$task,$activity]),$data)->assertRedirect();
        $this->assertSame('done',$activity->fresh()->status); $this->assertSame('to do',$task->fresh()->status);
        $this->post($url,['title'=>'','activity_date'=>'2026-02-30','status'=>'invalid'])->assertSessionHasErrors(['title','activity_date','status']);
        $this->actingAs($supervisor)->get(route('tasks.show',$task))->assertOk()->assertSee('Demo notes')->assertDontSee('id="add-activity-heading"',false);
        $this->post($url,$data)->assertForbidden();
        $this->get(route('tasks.activities.edit',[$task,$activity]))->assertForbidden();
        $this->patch(route('tasks.activities.update',[$task,$activity]),$data)->assertForbidden();
        $this->actingAs($other)->get(route('tasks.show',$task))->assertForbidden();
        $this->post($url,$data)->assertForbidden();
        $otherTask=new Task(['title'=>'Other','due_date'=>Task::today(),'priority'=>'low','status'=>'to do']); $otherTask->owner=$owner->name; $otherTask->user_id=$owner->id; $otherTask->description=''; $otherTask->save();
        $this->actingAs($owner)->patch(route('tasks.activities.update',[$otherTask,$activity]),$data)->assertNotFound();
    }
    public function test_pagination_and_inline_activity_submission(): void {
        $owner=User::factory()->create();
        for($i=0;$i<12;$i++) {
            $task=new Task(['title'=>'Demo '.$i,'due_date'=>Task::today(),'priority'=>'low','status'=>'to do']);
            $task->owner=$owner->name; $task->user_id=$owner->id; $task->description=''; $task->save();
        }
        $this->actingAs($owner)->get('/')->assertOk()->assertViewHas('tasks',fn($tasks)=>$tasks->count()===10 && $tasks->total()===12)->assertSee('Next');
        $this->get('/?page=2')->assertOk()->assertViewHas('tasks',fn($tasks)=>$tasks->count()===2)->assertSee('Previous');
        $task=Task::latest('id')->firstOrFail();
        $data=['title'=>'Inline demo','notes'=>'Sample notes','activity_date'=>Task::today(),'status'=>'to do','inline_task_id'=>$task->id,'page'=>2];
        $url=route('tasks.activities.store',$task);
        $this->from('/?page=2')->post($url,$data)->assertRedirect(route('tasks.index',['page'=>2]).'#task-activities-'.$task->id);
        $this->get('/?page=2')->assertSee('Inline demo')->assertSee('Sample notes');
        $data['title']='';
        $this->from('/?page=2')->post($url,$data)->assertSessionHasErrors(['title'],null,'inlineActivity');
        $this->get('/?page=2')->assertOk()->assertSee('id="task-activities-'.$task->id.'"  open',false)->assertSee('role="alert"',false);
        $this->assertSame(1,$task->activities()->count());
    }}