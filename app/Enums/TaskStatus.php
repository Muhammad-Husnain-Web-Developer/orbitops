<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TaskStatus: string
{
    use HasOptions;

    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::Todo => 'To Do',
            self::InProgress => 'In Progress',
            self::Review => 'Review',
            self::Done => 'Done',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Backlog => 'neutral',
            self::Todo => 'info',
            self::InProgress => 'accent',
            self::Review => 'warning',
            self::Done => 'success',
        };
    }
}
