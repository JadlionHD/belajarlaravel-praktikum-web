<?php

test('pertemuan 6 local vue page is accessible via inertia route', function () {
    $response = $this->get('/pertemuan6');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Pertemuan6'));
});
