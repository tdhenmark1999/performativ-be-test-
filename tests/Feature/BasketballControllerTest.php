<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class BasketballControllerTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Test creating a new player.
     *
     * @return void
     */
    public function testCreatePlayer()
    {
        $playerData = [
            'name' => 'John Doe',
            'team' => 'Lakers',
            'position' => 'Forward',
            'number' => 0,
        ];

        $response = $this->postJson('/api/players', $playerData);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'john doe',
            'team' => 'lakers',
            'position' => 'forward',
            'number' => 0,
        ]);
    }
}
