<?php

test('guests can view the public welcome page', function () {
    $response = $this->get('/');

    $response->assertOk();
});
