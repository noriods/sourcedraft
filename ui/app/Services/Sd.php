<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

/** Everything the UI knows about the engine: it calls `sd` and reads run folders. */
class Sd
{
    public function root(): string
    {
        return rtrim(config('sourcedraft.root'), '/');
    }

    /** Run `sd <args>` and return stdout; throws with the engine's message on failure. */
    public function run(array $args): string
    {
        $r = Process::path($this->root())->timeout(600)
            ->run([config('sourcedraft.python'), '-m', 'sourcedraft', ...$args]);
        if ($r->failed()) {
            $lines = array_filter(explode("\n", trim($r->errorOutput() ?: $r->output())));
            throw new \RuntimeException((string) end($lines));
        }

        return trim($r->output());
    }

    public static function safe(string $name): string
    {
        abort_unless(preg_match('/^[A-Za-z0-9][A-Za-z0-9._ -]*$/', $name) && ! str_contains($name, '..'), 404);

        return $name;
    }

    public function projects(): array
    {
        return array_map(fn ($f) => basename(dirname($f)), glob($this->root().'/projects/*/project.yaml'));
    }

    public function projectDir(string $p): string
    {
        $d = $this->root().'/projects/'.self::safe($p);
        abort_unless(is_file("$d/project.yaml"), 404);

        return $d;
    }

    public function config(string $p): array
    {
        return $this->parseYaml(file_get_contents($this->projectDir($p).'/project.yaml'));
    }

    /** project.yaml is flat enough for what the screen shows: model, privacy, recipes. */
    private function parseYaml(string $y): array
    {
        preg_match('/^\s*model:\s*(.+)$/m', $y, $m);
        preg_match('/^privacy:\s*(.+)$/m', $y, $pr);
        preg_match('/^recipes:\s*\[(.+)\]/m', $y, $rc);

        return [
            'model' => trim($m[1] ?? ''),
            'privacy' => trim($pr[1] ?? 'any'),
            'recipes' => array_map('trim', explode(',', $rc[1] ?? '')),
        ];
    }

    public function sources(string $p): array
    {
        $dir = $this->projectDir($p).'/sources';

        return array_map('basename', array_filter(glob("$dir/*"), 'is_file'));
    }

    public function chunkCount(string $p): ?int
    {
        $db = $this->projectDir($p).'/index.db';
        if (! is_file($db)) {
            return null;
        }
        try {
            return (int) (new \PDO('sqlite:'.$db))->query('select count(*) from chunks')->fetchColumn();
        } catch (\PDOException) {
            return null;
        }
    }

    /** Newest first. */
    public function runs(string $p): array
    {
        $out = [];
        foreach (array_reverse(glob($this->root().'/runs/'.self::safe($p).'/*', GLOB_ONLYDIR)) as $d) {
            $out[] = ['id' => basename($d), 'status' => $this->json($d, 'status.json')['status'] ?? '?',
                'topic' => $this->json($d, 'manifest.json')['topic'] ?? '', 'recipe' => $this->json($d, 'manifest.json')['recipe'] ?? ''];
        }

        return $out;
    }

    public function runDir(string $p, string $r): string
    {
        $d = $this->root().'/runs/'.self::safe($p).'/'.self::safe($r);
        abort_unless(is_dir($d), 404);

        return $d;
    }

    public function json(string $dir, string $file): array
    {
        return is_file("$dir/$file") ? json_decode(file_get_contents("$dir/$file"), true) : [];
    }

    /** Office files in the run folder the person may download. */
    public function files(string $dir): array
    {
        return array_map('basename', glob("$dir/*.{pptx,docx}", GLOB_BRACE));
    }

    /** PNG per slide of the newest deck in the run; made once with LibreOffice + pdftoppm. */
    public function slideImages(string $dir): array
    {
        $deck = is_file("$dir/final.pptx") ? 'final' : 'draft';
        if (! is_file("$dir/$deck.pptx")) {
            return [];
        }
        $img = "$dir/slides-$deck";
        if (! glob("$img/*.png")) {
            @mkdir($img);
            $profile = storage_path('app/lo-profile');
            Process::timeout(180)->run([config('sourcedraft.soffice'), "-env:UserInstallation=file://$profile", '--headless',
                '--convert-to', 'pdf', '--outdir', $img, "$dir/$deck.pptx"])->throw();
            Process::timeout(120)->run(['pdftoppm', '-r', '80', '-png', "$img/$deck.pdf", "$img/s"])->throw();
        }

        return array_map('basename', glob("$img/*.png"));
    }
}
