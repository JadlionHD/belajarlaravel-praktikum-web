@if (session('success'))
    <p role="status">{{ session('success') }}</p>
@endif
@if ($errors->any())
    <div role="alert">
        <p>Periksa kembali isian berikut:</p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
