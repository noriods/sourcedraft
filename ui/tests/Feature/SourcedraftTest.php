<?php

namespace Tests\Feature;

use App\Jobs\RunDraft;
use App\Models\DraftJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SourcedraftTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/sd-ui-test-'.uniqid();
        $run = "{$this->root}/runs/demo/r1";
        mkdir("{$this->root}/projects/demo/sources", 0777, true);
        mkdir($run, 0777, true);
        file_put_contents("{$this->root}/projects/demo/project.yaml", "provider:\n  model: m\nprivacy: any\nrecipes: [teaching-deck]\n");
        $files = [
            'status.json' => ['status' => 'draft'],
            'manifest.json' => ['topic' => 'T', 'recipe' => 'teaching-deck'],
            'validation.json' => ['ok' => true, 'errors' => []],
            'retrieved.json' => [['id' => 'a.md#x', 'file' => 'a.md', 'text' => 'quoted source text']],
            'model.json' => ['parsed' => ['title' => 'T', 'slides' => [['title' => 'S', 'points' => [['text' => 'P', 'sources' => ['a.md#x']]], 'notes' => []]]]],
        ];
        foreach ($files as $f => $v) {
            file_put_contents("$run/$f", json_encode($v));
        }
        touch("$run/draft.pptx");
        config(['sourcedraft.root' => $this->root]);
        $this->actingAs(User::factory()->create());
    }

    public function test_ingest_calls_the_engine(): void
    {
        Process::fake(['*' => Process::result('12 chunks indexed')]);
        $this->post('/projects/demo/ingest')->assertRedirect('/projects/demo')->assertSessionHas('ok');
        Process::assertRan(fn ($p) => in_array('ingest', $p->command) && in_array('demo', $p->command));
    }

    public function test_draft_is_queued_not_run(): void
    {
        Queue::fake();
        $this->post('/projects/demo/draft', ['recipe' => 'teaching-deck', 'topic' => 'Decisions'])->assertRedirect();
        Queue::assertPushed(RunDraft::class);
        $this->assertSame('queued', DraftJob::first()->status);
    }

    public function test_review_shows_the_quoted_source(): void
    {
        Process::fake();
        $this->get('/projects/demo/runs/r1')->assertOk()->assertSee('quoted source text')->assertSee('Citation check passed');
    }

    public function test_approve_is_blocked_without_a_name(): void
    {
        Process::fake();
        $this->post('/projects/demo/runs/r1/approve', ['by' => '', 'confirm' => '1'])->assertSessionHasErrors('by');
        Process::assertNothingRan();
    }

    public function test_approve_calls_the_engine_with_the_name(): void
    {
        Process::fake(['*' => Process::result('approved and exported final')]);
        $this->post('/projects/demo/runs/r1/approve', ['by' => 'Dr Smith', 'confirm' => '1'])->assertSessionHasNoErrors();
        Process::assertRan(fn ($p) => in_array('approve', $p->command) && in_array('Dr Smith', $p->command));
    }

    public function test_final_download_only_after_approval(): void
    {
        $this->get('/projects/demo/runs/r1/file/final.pptx')->assertNotFound();
        $this->get('/projects/demo/runs/r1/file/draft.pptx')->assertOk();
        touch("{$this->root}/runs/demo/r1/final.pptx"); // what the engine writes on approval
        $this->get('/projects/demo/runs/r1/file/final.pptx')->assertOk();
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->get('/projects/..%2F..')->assertNotFound();
    }
}
