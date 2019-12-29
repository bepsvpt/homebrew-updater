<?php

namespace Tests\Checker;

use App\Checkers\Github;
use App\Models\Formula;
use Tests\TestCase;

class GithubTest extends TestCase
{
    public function test_github_latest_version_method()
    {
        $path = base_path('composer.json');

        $content = file_get_contents($path);

        $expected = json_decode($content, true)['version'];

        /** @var Formula $formula */

        $formula = Formula::query()->create([
            'name' => 'homebrew/updater',
            'repo' => 'bepsvpt/homebrew-updater',
            'checker' => 'Github',
            'git' => ['path' => null],
        ]);

        $checker = new Github($formula);

        $this->assertSame($expected, $checker->latest());
    }
}
