<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $admin_user_id
 * @property int|null $impersonated_user_id
 * @property string $reference_number
 * @property string $reason
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $status
 */
#[Fillable([
    'admin_user_id',
    'impersonated_user_id',
    'reference_number',
    'reason',
    'started_at',
    'ended_at',
    'ip_address',
    'user_agent',
    'status',
])]
class UserImpersonationLog extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
