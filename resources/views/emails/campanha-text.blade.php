DoaVida

{{ $campanha->titulo }}
@if($campanha->subtitulo)
{{ $campanha->subtitulo }}
@endif

Olá, {{ $doador->name }}.

{{ $campanha->descricao ?: 'Estamos com uma campanha ativa e sua participação pode fazer a diferença para quem precisa de sangue.' }}

@if($bloodType)
Campanha direcionada para o tipo sanguíneo {{ $bloodType }}.
@else
Campanha aberta para todos os tipos sanguíneos cadastrados.
@endif

Entre para agendar sua doação:
{{ $ctaUrl }}

Antes de doar, confirme se você está bem alimentado, hidratado e levando um documento oficial com foto.

Equipe DoaVida
