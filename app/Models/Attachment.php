<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['workspace_id', 'attachable_type', 'attachable_id', 'project_id', 'client_id', 'uploaded_by', 'name', 'disk', 'path', 'mime_type', 'size', 'visible_to_client'])]
class Attachment extends Model
{
    use BelongsToWorkspace;

    protected static function booted(): void
    {
        static::deleted(function (Attachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'visible_to_client' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * A coarse file kind used for icons and filters.
     */
    public function kind(): string
    {
        $mime = (string) $this->mime_type;
        $extension = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            $mime === 'application/pdf' => 'pdf',
            in_array($extension, ['zip', 'rar', '7z', 'gz'], true) => 'archive',
            in_array($extension, ['xls', 'xlsx', 'csv', 'numbers'], true) => 'spreadsheet',
            in_array($extension, ['fig', 'sketch', 'psd', 'ai', 'xd'], true) => 'design',
            in_array($extension, ['doc', 'docx', 'txt', 'md', 'pages', 'rtf'], true) => 'document',
            default => 'file',
        };
    }
}
