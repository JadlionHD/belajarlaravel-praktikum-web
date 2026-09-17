<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Tiket</title>
</head>
<body>
    <h1>Detail Tiket #{{ $ticket['id'] }}</h1>
    <p>Subjek: {{ $ticket['subject'] }}</p>
    <p>Status: {{ $ticket['status'] }}</p>
    <a href="{{ route('tickets.index') }}">Kembali ke daftar</a>
</body>
</html>
