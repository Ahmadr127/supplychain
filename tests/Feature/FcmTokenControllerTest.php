<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class FcmTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_register_fcm_token()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/fcm-token', [
            'device_token' => 'test_token_123456789',
            'device_type' => 'android',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FCM Token berhasil didaftarkan',
            ]);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $this->user->id,
            'device_token' => 'test_token_123456789',
            'device_type' => 'android',
        ]);
    }

    /** @test */
    public function it_updates_existing_token_instead_of_creating_duplicate()
    {
        Sanctum::actingAs($this->user);

        // Create initial token
        UserDeviceToken::create([
            'user_id' => $this->user->id,
            'device_token' => 'test_token_123456789',
            'device_type' => 'android',
        ]);

        // Register same token again with different device type
        $response = $this->postJson('/api/fcm-token', [
            'device_token' => 'test_token_123456789',
            'device_type' => 'ios',
        ]);

        $response->assertStatus(200);

        // Should only have one record
        $this->assertEquals(1, UserDeviceToken::where('device_token', 'test_token_123456789')->count());
        
        // Device type should be updated
        $this->assertDatabaseHas('user_device_tokens', [
            'device_token' => 'test_token_123456789',
            'device_type' => 'ios',
        ]);
    }

    /** @test */
    public function it_requires_device_token_to_register()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/fcm-token', [
            'device_type' => 'android',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_token']);
    }

    /** @test */
    public function it_validates_device_type_enum()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/fcm-token', [
            'device_token' => 'test_token_123456789',
            'device_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_type']);
    }

    /** @test */
    public function it_can_remove_fcm_token()
    {
        Sanctum::actingAs($this->user);

        // Create token first
        UserDeviceToken::create([
            'user_id' => $this->user->id,
            'device_token' => 'test_token_to_delete',
            'device_type' => 'android',
        ]);

        $response = $this->deleteJson('/api/fcm-token', [
            'device_token' => 'test_token_to_delete',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'FCM Token berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('user_device_tokens', [
            'device_token' => 'test_token_to_delete',
        ]);
    }

    /** @test */
    public function it_only_removes_token_belonging_to_authenticated_user()
    {
        $otherUser = User::factory()->create();
        
        Sanctum::actingAs($this->user);

        // Create token for other user
        UserDeviceToken::create([
            'user_id' => $otherUser->id,
            'device_token' => 'other_user_token',
            'device_type' => 'android',
        ]);

        // Try to delete other user's token
        $response = $this->deleteJson('/api/fcm-token', [
            'device_token' => 'other_user_token',
        ]);

        $response->assertStatus(200);

        // Token should still exist
        $this->assertDatabaseHas('user_device_tokens', [
            'device_token' => 'other_user_token',
            'user_id' => $otherUser->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_register_token()
    {
        $response = $this->postJson('/api/fcm-token', [
            'device_token' => 'test_token_123456789',
            'device_type' => 'android',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_to_remove_token()
    {
        $response = $this->deleteJson('/api/fcm-token', [
            'device_token' => 'test_token_123456789',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_send_test_notification()
    {
        Sanctum::actingAs($this->user);

        // Create device token for user
        UserDeviceToken::create([
            'user_id' => $this->user->id,
            'device_token' => 'test_token_for_notification',
            'device_type' => 'android',
        ]);

        $response = $this->postJson('/api/test-notification', [
            'title' => 'Custom Test Title',
            'body' => 'Custom test body message',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Test notification berhasil dikirim',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'device_count',
                    'title',
                    'body',
                ],
            ]);

        // Verify in-app notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Custom Test Title',
            'body' => 'Custom test body message',
        ]);
    }

    /** @test */
    public function it_uses_default_title_and_body_for_test_notification()
    {
        Sanctum::actingAs($this->user);

        // Create device token for user
        UserDeviceToken::create([
            'user_id' => $this->user->id,
            'device_token' => 'test_token_for_notification',
            'device_type' => 'android',
        ]);

        $response = $this->postJson('/api/test-notification');

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Notifikasi')
            ->assertJsonPath('data.body', 'Ini adalah test notification dari Supply Chain API');
    }

    /** @test */
    public function it_returns_error_when_no_device_tokens_for_test_notification()
    {
        Sanctum::actingAs($this->user);

        // Don't create any device tokens

        $response = $this->postJson('/api/test-notification', [
            'title' => 'Test',
            'body' => 'Test body',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Tidak ada device token terdaftar untuk user ini',
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_send_test_notification()
    {
        $response = $this->postJson('/api/test-notification');

        $response->assertStatus(401);
    }
}
