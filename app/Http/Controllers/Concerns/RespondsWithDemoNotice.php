<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

/**
 * Temporary helper: write actions only flash a message until you add real logic.
 * Delete the trait usage from a controller once its store/update/destroy are implemented.
 */
trait RespondsWithDemoNotice
{
    protected function demo(string $message): RedirectResponse
    {
        return back()->with('success', $message.' (demo only: connect your own logic here)');
    }
}
