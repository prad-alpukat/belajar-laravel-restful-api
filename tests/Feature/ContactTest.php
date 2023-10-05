<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Contact;
use Database\Seeders\UserSeeder;
use Database\Seeders\SearchSeeder;
use Database\Seeders\ContactSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactTest extends TestCase
{
    public function testCreateSuccess()
    {
        $this->seed([UserSeeder::class]);

        $this->post('/api/contacts', [
            'first_name' => "Praditya",
            'last_name' => "Aldi Syahputra",
            'email' => 'example@gmail.com',
            'phone' => '08123123123'
        ], [
            'Authorization' => 'test'
        ])->assertStatus(201)->assertJson(['data' => [
            'first_name' => "Praditya",
            'last_name' => "Aldi Syahputra",
            'email' => 'example@gmail.com',
            'phone' => '08123123123'
        ]]);
    }
    public function testCreateFailed()
    {
        $this->seed([UserSeeder::class]);

        $this->post('/api/contacts', [
            'first_name' => "",
            'last_name' => "Aldi Syahputra",
            'email' => 'exampl',
            'phone' => '08123123123'
        ], [
            'Authorization' => 'test'
        ])->assertStatus(400)->assertJson(['errors' => [
            'first_name' => [
                'The first name field is required.'
            ],
            'email' => [
                'The email field must be a valid email address.'
            ]
        ]]);
    }
    public function testCreateUnauthorized()
    {
        $this->seed([UserSeeder::class]);

        $this->post('/api/contacts', [
            'first_name' => "",
            'last_name' => "Aldi Syahputra",
            'email' => 'exampl',
            'phone' => '08123123123'
        ], [
            'Authorization' => 'salah'
        ])->assertStatus(401)->assertJson(['errors' => [
            'message' => [
                'unauthorized'
            ]
        ]]);
    }

    public function testGetSuccess()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();

        $this->get('/api/contacts/' . $contact->id, [
            'Authorization' => 'test'
        ])->assertStatus(200)->assertJson(["data" => [
            'first_name' => 'test',
            'last_name' => 'test',
            'email' => 'test@gmail.com',
            'phone' => '08123123123',
        ]]);
    }

    public function testGetNotFound()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();

        $this->get('/api/contacts/' . ($contact->id + 1), [
            'Authorization' => 'test'
        ])->assertStatus(404)->assertJson(["errors" => [
            "message" => ["not found"]
        ]]);
    }

    public function testGetOtherContact()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);
        $contact = Contact::query()->limit(1)->first();

        $this->get('/api/contacts/' . $contact->id, [
            'Authorization' => 'test2'
        ])->assertStatus(404)->assertStatus(404)->assertJson(["errors" => [
            "message" => ["not found"]
        ]]);
    }

    public function testUpdateSuccess()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();

        $this->put(
            '/api/contacts/' . $contact->id,
            [
                'first_name' => 'test2',
                'last_name' => 'test2',
                'email' => 'test2@gmail.com',
                'phone' => '08123123122',
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)->assertJson(["data" => [
            'first_name' => 'test2',
            'last_name' => 'test2',
            'email' => 'test2@gmail.com',
            'phone' => '08123123122',
        ]]);
    }

    public function testUpdateValidationError()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();

        $this->put(
            '/api/contacts/' . $contact->id,
            [
                'first_name' => '',
                'last_name' => 'test2',
                'email' => 'test2@gmail.com',
                'phone' => '08123123122',
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(400)->assertJson(["errors" => [
            'first_name' => ['The first name field is required.'],
        ]]);
    }

    // test delete
    public function testDeleteSuccess()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();

        $this->delete(
            '/api/contacts/' . $contact->id,
            [],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)->assertJson(["data" => true]);
    }
    public function testDeleteNotFound()
    {
        $this->seed([UserSeeder::class, ContactSeeder::class]);

        $contact = Contact::query()->limit(1)->first();

        $this->delete(
            '/api/contacts/' . ($contact->id + 1),
            [],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(404)
            ->assertJson(
                [
                    "errors" =>
                    ["message" => ["not found"]]
                ]
            );
    }

    // search
    public function testSearchByFirstName()
    {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?name=first', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));

        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }
    public function testSearchByLastName()
    {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?name=last', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));

        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchByEmail()
    {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?email=test', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));

        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchByPhone()
    {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?name=tidakada', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));

        self::assertEquals(0, count($response['data']));
        self::assertEquals(0, $response['meta']['total']);
    }

    public function testSearchNotFound()
    {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?phone=0812312312', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));

        self::assertEquals(10, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
    }

    public function testSearchWithPage()
    {
        $this->seed([UserSeeder::class, SearchSeeder::class]);

        $response = $this->get('/api/contacts?size=5&page=2', ['Authorization' => 'test'])
            ->assertStatus(200)
            ->json();

        Log::info(json_encode($response, JSON_PRETTY_PRINT));

        self::assertEquals(5, count($response['data']));
        self::assertEquals(20, $response['meta']['total']);
        self::assertEquals(2, $response['meta']['current_page']);
    }
}
