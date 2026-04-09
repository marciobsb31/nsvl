<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Token para Walyson (Gestor Nacional - federal)
$walyson = App\Models\Usuario::where('cpf', '73583278100')->first();
if ($walyson) {
    $token = $walyson->createToken('debug')->plainTextToken;
    echo "TOKEN_WALYSON=" . $token . "\n";
} else {
    echo "WALYSON_NOT_FOUND\n";
}

// IDs das solicitações de Maria
$maria = App\Models\Usuario::where('cpf', '11144477735')->first();
if ($maria) {
    $sols = \App\Models\SolicitacaoCadastro::where('user_id', $maria->id)->get(['id', 'esfera_id']);
    foreach ($sols as $s) {
        $esfera = \App\Models\Esfera::find($s->esfera_id);
        echo "MARIA_SOL_ID=" . $s->id . " ESFERA=" . ($esfera->nome ?? '?') . "\n";
    }
    $perfis = \App\Models\PerfilUsuario::where('usuario_id', $maria->id)->get();
    foreach ($perfis as $pu) {
        $p = \App\Models\Perfil::find($pu->perfil_id);
        echo "MARIA_PU_ID=" . $pu->id . " PERFIL=" . ($p->nome ?? '?') . " ATIVO=" . ($pu->ativo ? 'true' : 'false') . "\n";
    }
}
