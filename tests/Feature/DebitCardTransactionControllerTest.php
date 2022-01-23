<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
        $response = $this->get('api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);

    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);
        // get /debit-card-transactions
        $response = $this->get('api/debit-card-transactions', [
            'debit_card_id' => $this->otherDebitCard->id
        ]);

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $amount = 750000;
        $currency = DebitCardTransaction::CURRENCY_IDR;

        $response = $this->post('api/debit-card-transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => $amount,
            'currency_code' => $currency
        ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED);
        $response->assertJsonPath('amount', $amount);
        $response->assertJsonPath('currency_code', $currency);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $amount = 750000;
        $currency = DebitCardTransaction::CURRENCY_IDR;

        $response = $this->post('api/debit-card-transactions', [
            'debit_card_id' => $otherDebitCard->id,
            'ammount' => $amount,
            'currency_code' => $currency
        ]);

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);

    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $response = $this->get('api/debit-card-transactions/' . $this->debitCard->id);

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->get('api/debit-card-transactions/' . $otherDebitCard->id);

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    // Extra bonus for extra tests :)
}
