<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        DebitCard::factory()->active()->count(2)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response
                ->assertStatus(Response::HTTP_OK)
                ->assertJsonCount(2);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $otherUser = User::factory()->create();

        // Current user debit
        DebitCard::factory()->active()->count(2)->create([
            'user_id' => $this->user->id
        ]);

        // Other user debit
        DebitCard::factory()->active()->count(4)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response
                ->assertStatus(Response::HTTP_OK)
                ->assertJsonCount(2);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $card = DebitCard::factory()->make();

        // post /debit-cards
        $response = $this->postJson('/api/debit-cards', [
            'type' => $card->type
        ]);

        $response
            ->assertStatus(Response::HTTP_CREATED);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $card = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        // get api/debit-cards/{debitCard}
        $response = $this->getJson('api/debit-cards/'.$card->id, [
            'type' => $card->type
        ]);

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'id' => $card->id,
                'number' => $card->number,
                'type' => $card->type,
                'expiration_date' => $card->expiration_date->format('Y-m-d H:i:s'),
                'is_active' => $card->is_active,
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();

        // Other user debit
        $otherCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id
        ]);

        // get api/debit-cards/{debitCard}
        $response = $this->getJson('/api/debit-cards/'.$otherCard->id);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $card = DebitCard::factory()->expired()->create([
            'user_id' => $this->user->id,
        ]);

        // put api/debit-cards/{debitCard}
        $response = $this->putJson('/api/debit-cards/'.$card->id, [
            'is_active' => true
        ]);

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('is_active', true);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $card = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        // put api/debit-cards/{debitCard}
        $response = $this->putJson('/api/debit-cards/'.$card->id, [
            'is_active' => false
        ]);

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('is_active', false);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $card = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        // put api/debit-cards/{debitCard}
        $response = $this->putJson('/api/debit-cards/'.$card->id);

        $response
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $card = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        // delete api/debit-cards/{debitCard}
        $response = $this->deleteJson('/api/debit-cards/'.$card->id);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $otherUser = User::factory()->create();

        // Other user debit
        $otherCard = DebitCard::factory()->active()->create([
            'user_id' => $otherUser->id
        ]);

        // delete api/debit-cards/{debitCard}
        $response = $this->deleteJson('/api/debit-cards/'.$otherCard->id);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    // Extra bonus for extra tests :)
}
