<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id);

        $response
                ->assertStatus(Response::HTTP_OK)
                ->assertJsonCount(2);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $cardOtherUser = DebitCard::factory()->create();
        DebitCardTransaction::factory()->count(2)->create([
            'debit_card_id' => $cardOtherUser->id
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id='.$cardOtherUser->id);

        $response
                ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->make([
            'debit_card_id' => $this->debitCard->id
        ]);

        // post /debit-card-transactions
        $response = $this->postJson('/api/debit-card-transactions?debit_card_id='.$this->debitCard->id, [
            'amount' => $transaction->amount,
            'currency_code' => $transaction->currency_code
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $otherCard = DebitCard::factory()->create();
        $transaction = DebitCardTransaction::factory()->make([
            'debit_card_id' => $otherCard->id
        ]);

        // post /debit-card-transactions
        $response = $this->postJson('/api/debit-card-transactions?debit_card_id='.$otherCard->id, [
            'amount' => $transaction->amount,
            'currency_code' => $transaction->currency_code
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id
        ]);

        // get /debit-card-transactions/{debitCardTransaction}
        $response = $this->getJson('/api/debit-card-transactions/'.$transaction->id);

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'amount' => $transaction->amount,
                'currency_code' => $transaction->currency_code,
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $transaction = DebitCardTransaction::factory()->create();

        // get /debit-card-transactions/{debitCardTransaction}
        $response = $this->getJson('/api/debit-card-transactions/'.$transaction->id);

        $response
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    // Extra bonus for extra tests :)
}
