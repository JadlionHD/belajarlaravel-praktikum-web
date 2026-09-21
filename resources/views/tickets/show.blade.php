<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Detail tiket</title></head>
<body>
@include('tickets._messages')
<h1>#{{ $ticket->id }} — {{ $ticket->subject }}</h1>
<p>{{ $ticket->description }}</p>
<p>Kategori: {{ $ticket->category->name }}</p>
<p>Pemilik: {{ $ticket->user->name }}</p>
<p>Status: {{ $ticket->status }}; urgent: {{ $ticket->is_urgent ? 'Ya' : 'Tidak' }}</p>
<a href="{{ route('tickets.edit', $ticket) }}">Edit</a>
<form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
      onsubmit="return confirm('Hapus tiket beserta komentarnya?')">
    @csrf
    @method('DELETE')
    <button type="submit">Hapus</button>
</form>
<h2>Komentar</h2>
@forelse ($ticket->comments as $comment)
    <p>{{ $comment->user->name }}: {{ $comment->body }}</p>
@empty
    <p>Belum ada komentar.</p>
@endforelse
<a href="{{ route('tickets.index') }}">Daftar tiket</a>
</body></html>
