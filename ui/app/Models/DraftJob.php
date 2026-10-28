<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class DraftJob extends Model
{
    public function pending(): bool
    {
        return in_array($this->status, ['queued', 'running']);
    }
}
