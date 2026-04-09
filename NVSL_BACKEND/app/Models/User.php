<?php

namespace App\Models;

/**
 * Alias para compatibilidade com Sanctum e auth guard do Laravel.
 * O model real é App\Models\Usuario.
 */
class User extends Usuario
{
    protected $table = 'usuarios';
}
