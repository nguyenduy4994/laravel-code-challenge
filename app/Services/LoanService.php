<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE
        ]);

        $monthlyAmount = floor($loan->amount / $loan->terms);
        $lastMonthAmount = $loan->amount - ($monthlyAmount * ($loan->terms-1));

        for($i = $terms,$currentMonth = 1; $i > 0; $i--,$currentMonth++) {
            ScheduledRepayment::create([
                'loan_id' => $loan->id,
                'amount' => ($i == 1) ? $lastMonthAmount : $monthlyAmount,
                'outstanding_amount' => ($i == 1) ? $lastMonthAmount : $monthlyAmount,
                'currency_code' => $currencyCode,
                'due_date' => Carbon::createFromFormat('Y-m-d', $processedAt)->addMonth($currentMonth)->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
        }

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        try {
            DB::beginTransaction();

            $receive = ReceivedRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);

            while($amount > 0) {
                $schedule = $loan->scheduledRepayments()
                    ->where('outstanding_amount', '>', 0)
                    ->where('status', ScheduledRepayment::STATUS_DUE)
                    ->orderBy('id')->first();

                if (! $schedule) {
                    return $receive;
                }

                $schedule->outstanding_amount -= $amount;
                if ($schedule->outstanding_amount < 0) {
                    $schedule->outstanding_amount = 0;
                }

                if ($schedule->outstanding_amount == 0) {
                    $schedule->status = ScheduledRepayment::STATUS_REPAID;
                } else {
                    $schedule->status = ScheduledRepayment::STATUS_PARTIAL;
                }
                $schedule->save();

                $amount -= ($schedule->amount + $schedule->outstanding_amount);
            }

            $loan->outstanding_amount -= $receive->amount;
            if ($loan->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_DUE)->count() == 0) {
                $loan->outstanding_amount = 0;
                $loan->status = Loan::STATUS_REPAID;
            }
            $loan->save();

            DB::commit();
            return $receive;
        } catch (Exception $ex) {
            DB::rollBack();
            return false;
        }
    }
}
