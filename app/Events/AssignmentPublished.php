<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class AssignmentPublished implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $assignmentId) {}
}
