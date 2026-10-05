<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'company', 'team_size', 'topic', 'message', 'ip_address'])]
class ContactMessage extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }
}
