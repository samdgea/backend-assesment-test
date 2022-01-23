<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\AssertableJson;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
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

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $response = $this->get('api/debit-cards');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $response = $this->get('api/debit-cards');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $response = $this->post('api/debit-cards', [
            'type' => 'visa'
        ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $response = $this->get('api/debit-cards/' . $this->debitCard->id);
        $response->assertStatus(HttpResponse::HTTP_OK);
    }
    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $otherDebit = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // get api/debit-cards/{debitCard}
        $response = $this->get('api/debit-cards/' . $otherDebit->id);

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $response = $this->put('api/debit-cards/1',[
            'is_active' => true
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('is_active', true);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $response = $this->put('api/debit-cards/1',[
            'is_active' => false
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('is_active', false);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $response = $this->json('PUT', 'api/debit-cards/1', [
            'balance' => 25000000000
        ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('message', 'The given data was invalid.');
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $response = $this->delete('api/debit-cards/' . $this->debitCard->id);

        $response->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCardWithTrx = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);

        $debitCardTrx = DebitCardTransaction::factory()->create([
            'debit_card_id' => $debitCardWithTrx->id
        ]);

        $response = $this->delete('api/debit-cards/' . $debitCardWithTrx->id);

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    // Extra bonus for extra tests :)
    public function testCutomerCannotDeactiveOtherUserDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->put("api/debit-cards/$otherUserDebitCard->id", [
            'is_active' => false
        ]);
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    public function testCustomerCannotActiveOtherUserDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->put("api/debit-cards/$otherUserDebitCard->id", [
            'is_active' => true
        ]);
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    public function testCustomerCannotDeleteADebitCardBelongsToOtherUser()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->delete("api/debit-cards/$otherUserDebitCard->id");

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }
}
