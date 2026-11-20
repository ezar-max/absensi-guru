<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action',
        'table_name',
        'record_id',
        'detail',
    ];

    protected function casts(): array
    {
        return [
            'detail' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function log(
        ?User $actor,
        string $action,
        ?string $tableName = null,
        mixed $recordId = null,
        ?array $detail = null
    ): static {
        return static::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId !== null ? (string) $recordId : null,
            'detail' => $detail,
        ]);
    }
}

