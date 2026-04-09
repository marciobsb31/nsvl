<?php

namespace App\Mail;

use App\Models\SolicitacaoCadastro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitacaoCadastroEnviada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SolicitacaoCadastro $solicitacao
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'NVSL — Solicitação de Cadastro Recebida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitacao-cadastro-enviada',
        );
    }
}
