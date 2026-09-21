<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Tiket helpdesk</title></head>
<body>
<h1>Daftar tiket</h1>
@include('tickets._messages')
<a href="{{ route('tickets.create') }}">Buat tiket</a>
<table border="1" cellpadding="8">
<thead><tr><th>ID</th><th>Subjek</th><th>Kategori</th><th>Pemilik</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
@forelse ($tickets as $ticket)
<tr>
    <td>{{ $ticket->id }}</td><td>{{ $ticket->subject }}</td>
    <td>{{ $ticket->category->name }}</td><td>{{ $ticket->user->name }}</td>
    <td>{{ $ticket->status }}</td>
    <td><a href="{{ route('tickets.show', $ticket) }}">Detail</a>
        <a href="{{ route('tickets.edit', $ticket) }}">Edit</a></td>
</tr>
@empty
<tr><td colspan="6">Belum ada tiket.</td></tr>
@endforelse
</tbody></table>
<p>Halaman {{ $tickets->currentPage() }} dari {{ $tickets->lastPage() }}</p>
@if ($tickets->previousPageUrl())
<a href="{{ $tickets->previousPageUrl() }}">Sebelumnya</a>
@endif
@if ($tickets->nextPageUrl())
<a href="{{ $tickets->nextPageUrl() }}">Berikutnya</a>
@endif
</body></html>