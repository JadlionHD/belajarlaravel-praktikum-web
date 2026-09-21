<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Buat tiket</title></head>
<body>
<h1>Buat tiket</h1>
@include('tickets._messages')
<form method="POST" action="{{ route('tickets.store') }}">
    @csrf
    @include('tickets._form')
</form>
<a href="{{ route('tickets.index') }}">Kembali</a>
</body></html>
