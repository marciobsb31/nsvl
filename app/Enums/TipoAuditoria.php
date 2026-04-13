<?php

namespace App\Enums;

enum TipoAuditoria: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case INSERT = 'insert';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
}
