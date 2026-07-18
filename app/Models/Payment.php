<?php
declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\GymScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use GymScoped, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gym_id',
        'member_id',
        'amount',
        'payment_method',
        'paid_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
    ];

    /**
     * Get the gym this payment belongs to.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the member this payment belongs to.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
