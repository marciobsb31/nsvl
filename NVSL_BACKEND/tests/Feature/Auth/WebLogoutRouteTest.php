<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class WebLogoutRouteTest extends TestCase
{
    public function get_logout_redireciona_ao_login_do_frontend(): void
    {
        config([
            'govbr.frontend_url' => 'http://localhost',
            'govbr.frontend_login_path' => '/login',
        ]);

        $response = $this->get('/logout');

        $response->assertRedirect('http://localhost/login?from=logout');
    }
}
