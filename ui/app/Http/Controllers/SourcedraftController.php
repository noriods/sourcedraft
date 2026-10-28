<?php

namespace App\Http\Controllers;

use App\Jobs\RunDraft;
use App\Models\DraftJob;
use App\Services\Sd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SourcedraftController extends Controller
{
    public function __construct(private Sd $sd) {}

    public function login(Request $r)
    {
        if (Auth::attempt($r->only('email', 'password'))) {
            $r->session()->regenerate();

            return redirect('/');
        }

        return back()->withErrors(['email' => 'Wrong email or password.']);
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();

        return redirect('/login');
    }

    public function projects()
    {
        return view('projects', ['projects' => $this->sd->projects()]);
    }

    public function project(string $p)
    {
        $jobs = DraftJob::where('project', $p)->latest()->limit(5)->get();

        return view('project', [
            'p' => $p, 'cfg' => $this->sd->config($p), 'sources' => $this->sd->sources($p), 'chunks' => $this->sd->chunkCount($p),
            'runs' => $this->sd->runs($p), 'jobs' => $jobs, 'refresh' => $jobs->contains->pending(),
        ]);
    }

    public function upload(Request $r, string $p)
    {
        $r->validate(['file' => 'required|file|max:51200|extensions:pptx,pdf,docx,md,txt']);
        $f = $r->file('file');
        $f->move($this->sd->projectDir($p).'/sources', basename($f->getClientOriginalName()));

        return redirect("/projects/$p")->with('ok', 'File added. Click Ingest to read it.');
    }

    public function ingest(string $p)
    {
        $this->sd->projectDir($p);
        try {
            $n = $this->sd->run(['ingest', $p]);

            return redirect("/projects/$p")->with('ok', "Ingested: $n.");
        } catch (\RuntimeException $e) {
            return redirect("/projects/$p")->withErrors(['ingest' => $e->getMessage()]);
        }
    }

    public function draft(Request $r, string $p)
    {
        $cfg = $this->sd->config($p);
        $v = $r->validate(['recipe' => ['required', 'in:'.implode(',', $cfg['recipes'])], 'topic' => 'required|string|max:300']);
        $j = DraftJob::create(['project' => $p, 'recipe' => $v['recipe'], 'topic' => $v['topic']]);
        RunDraft::dispatch($j->id);

        return redirect("/projects/$p")->with('ok', 'Drafting started. This page updates by itself.');
    }

    public function run(string $p, string $run)
    {
        $d = $this->sd->runDir($p, $run);
        $model = $this->sd->json($d, 'model.json')['parsed'] ?? null;
        $chunks = collect($this->sd->json($d, 'retrieved.json'))->keyBy('id');
        $status = $this->sd->json($d, 'status.json');
        $images = [];
        if (($model['slides'] ?? null) && $status['status'] !== 'failed') {
            try {
                $images = $this->sd->slideImages($d);
            } catch (\Throwable) {
            }
        }

        return view('run', [
            'p' => $p, 'run' => $run, 'model' => $model, 'chunks' => $chunks, 'status' => $status,
            'manifest' => $this->sd->json($d, 'manifest.json'), 'valid' => $this->sd->json($d, 'validation.json'),
            'images' => $images, 'files' => $this->sd->files($d),
            'imgDir' => is_file("$d/final.pptx") ? 'slides-final' : 'slides-draft',
        ]);
    }

    public function approve(Request $r, string $p, string $run)
    {
        $d = $this->sd->runDir($p, $run);
        $v = $r->validate(['by' => 'required|string|max:100', 'confirm' => 'accepted'],
            ['by.required' => 'Type your name to approve.', 'confirm.accepted' => 'Tick the box to confirm.']);
        try {
            $this->sd->run(['approve', "$p/$run", '--by', trim($v['by'])]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['by' => $e->getMessage()]);
        }

        return redirect("/projects/$p/runs/$run")->with('ok', 'Approved. The clean final file is ready below.');
    }

    public function file(string $p, string $run, string $name)
    {
        $d = $this->sd->runDir($p, $run);
        abort_unless(in_array($name, $this->sd->files($d), true), 404);

        return response()->download("$d/$name");
    }

    public function slide(string $p, string $run, string $dir, string $name)
    {
        $d = $this->sd->runDir($p, $run);
        abort_unless(in_array($dir, ['slides-draft', 'slides-final']) && preg_match('/^s-\d+\.png$/', $name), 404);

        return response()->file("$d/$dir/$name");
    }
}
