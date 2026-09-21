<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Edit tiket</title></head>
<body>
<h1>Edit tiket #{{ $ticket->id }}</h1>
@include('tickets._messages')
<form method="POST" action="{{ route('tickets.update', $ticket) }}">
    @csrf
    @method('PUT')
    @include('tickets._form')
</form>
<a href="{{ route('tickets.show', $ticket) }}">Batal</a>
</body></html>
