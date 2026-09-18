<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Tiket Helpdesk</title>
</head>

<body>
    <h1>Daftar Tiket</h1>
    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Subjek</th>
                <th>Kategori</th>
                <th>Pemilik</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
            <tr>
                <td>{{ $ticket->id }}</td>
                <td>{{ $ticket->subject }}</td>
                <td>{{ $ticket->category->name }}</td>
                <td>{{ $ticket->user->name }}</td>
                <td>{{ $ticket->status }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Belum ada tiket.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <p>Halaman {{ $tickets->currentPage() }} dari {{ $tickets->lastPage() }}</p>
    @if ($tickets->previousPageUrl())
    <a href="{{ $tickets->previousPageUrl() }}">Sebelumnya</a>
    @endif
    @if ($tickets->nextPageUrl())
    <a href="{{ $tickets->nextPageUrl() }}">Berikutnya</a>
    @endif
</body>

</html>