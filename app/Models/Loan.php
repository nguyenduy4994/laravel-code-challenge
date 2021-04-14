<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    public const STATUS_DUE = 'due';
    public const STATUS_REPAID = 'repaid';
    public const STATUSES = [
        self::STATUS_DUE,
        self::STATUS_REPAID
    ];

    public const CURRENCY_SGD = 'SGD';
    public const CURRENCY_VND = 'VND';

    public const TERM_3_MONTH = 3;
    public const TERM_6_MONTH = 6;
    public const TERMS = [
        self::TERM_3_MONTH,
        self::TERM_6_MONTH
    ];

    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'amount',
        'terms',
        'outstanding_amount',
        'currency_code',
        'processed_at',
        'status',
    ];

    /**
     * A Loan belongs to a User
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * A Loan has many Scheduled Repayments
     *
     * @return HasMany
     */
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class, 'loan_id');
    }

    protected static function booted()
    {
        static::creating(function ($loan) {
            $loan->outstanding_amount = $loan->amount;
        });
    }

    public function isFinishAllSchedule()
    {
        return $this->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_DUE)->count() == 0;
    }
}
