<?php

it('redirects the root path to pos', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin/pos');
});
