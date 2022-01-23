<?php

namespace Tests\Feature;

use App\Models\DebitCard;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->count(2)->create([
            'user_id' => $this->user->id
        ]);

        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $response = $this->get('api/debit-cards');

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonCount(2, '0.*');
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards

        $otherUser = User::factory()->create();
        Passport::actingAs($otherUser);

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
        $response = $this->get('api/debit-cards/1');
        $response->assertStatus(HttpResponse::HTTP_OK);
    }
    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $response = $this->get('api/debit-cards/3');

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
        $response = $this->put('api/debit-cards/1', [
            'balance' => 25000000000
        ]);

        $response->assertStatus(HTtpResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('message', 'The given data was invalid.');
        $response->assertJson(fn (AssertableJson $json) =>
            $json->hasAll('errors', 'errors.is_active')
        );
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $response = $this->delete('api/debit-cards/2');

        $response->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $response = $this->delete('api/debit-cards/1');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    // Extra bonus for extra tests :)
    public function testCutomerCannotDeactiveOtherUserDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherUserDebitCard = DebitCard::factory()->count(2)->create([
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
        $otherUserDebitCard = DebitCard::factory()->count(2)->create([
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
        $otherUserDebitCard = DebitCard::factory()->count(2)->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->delete("api/debit-cards/$otherUserDebitCard->id");

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }
}
