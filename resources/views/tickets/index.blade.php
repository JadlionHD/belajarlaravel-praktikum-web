<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Tiket</title>
</head>
<body>
    <h1>Daftar Tiket</h1>
    <ul>
        @foreach ($tickets as $ticket)
            <li>
                <a href="{{ route('tickets.show', ['ticket' => $ticket['id']]) }}">
                    #{{ $ticket['id'] }} - {{ $ticket['subject'] }}
                </a>
                ({{ $ticket['status'] }})
            </li>
        @endforeach
    </ul>
</body>
</html>
