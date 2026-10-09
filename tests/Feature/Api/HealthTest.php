<?php

test('the health endpoint returns an ok status', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});
