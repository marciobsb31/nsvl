<?php

namespace App\Mail;

use App\Models\SolicitacaoCadastro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitacaoCadastroAvaliada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SolicitacaoCadastro $solicitacao,
        public readonly string $status,
        public readonly ?string $justificativa = null,
    ) {}

    public function envelope(): Envelope
    {
        $assunto = $this->status === 'aprovado'
            ? 'NVSL - Solicitacao de Cadastro Aprovada'
            : 'NVSL - Solicitacao de Cadastro Reprovada';

        return new Envelope(subject: $assunto);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitacao-cadastro-avaliada',
        );
    }
}
