<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function can_register_new_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'access_token',
                'token_type'
            ])
            ->assertJson([
                'message' => 'User registered successfully',
                'token_type' => 'Bearer'
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function registration_validates_required_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function registration_validates_unique_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registration_validates_password_confirmation()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'access_token',
                'token_type'
            ])
            ->assertJson([
                'message' => 'Login successful',
                'token_type' => 'Bearer'
            ]);
    }

    /** @test */
    public function cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function can_get_user_profile_when_authenticated()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/profile');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
    }

    /** @test */
    public function cannot_get_profile_without_authentication()
    {
        $response = $this->getJson('/api/auth/profile');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_update_profile_when_authenticated()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully',
                'user' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function can_logout_when_authenticated()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        // Verify token works before logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/profile');
        $response->assertStatus(200);

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);

        // Verify token count is reduced
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    /** @test */
    public function can_logout_from_all_devices()
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('test_token_1')->plainTextToken;
        $token2 = $user->createToken('test_token_2')->plainTextToken;

        // Verify both tokens work initially
        $this->assertEquals(2, $user->fresh()->tokens()->count());

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out from all devices successfully'
            ]);

        // Verify all tokens are deleted
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    /** @test */
    public function protected_translation_routes_require_authentication()
    {
        // Test that translation routes require authentication
        $response = $this->getJson('/api/translations');
        $response->assertStatus(401);

        $response = $this->postJson('/api/translations', [
            'key' => 'test.key',
            'value' => 'Test Value',
            'locale' => 'en'
        ]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/translations/export');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_access_translation_routes()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/translations');

        $response->assertStatus(200);
    }

    /** @test */
    public function invalid_token_returns_unauthorized()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->getJson('/api/translations');

        $response->assertStatus(401);
    }

    /** @test */
    public function expired_token_returns_unauthorized()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;
        
        // Delete the token to simulate expiration
        $user->tokens()->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/translations');

        $response->assertStatus(401);
    }
} 