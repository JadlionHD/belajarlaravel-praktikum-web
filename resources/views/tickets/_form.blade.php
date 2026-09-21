<p><label>Subjek
    <input name="subject" value="{{ old('subject', $ticket->subject) }}"
           required maxlength="150">
</label></p>
@error('subject') <p role="alert">{{ $message }}</p> @enderror

<p><label>Deskripsi
    <textarea name="description" required maxlength="5000">{{ old('description', $ticket->description) }}</textarea>
</label></p>

<p><label>Kategori
    <select name="category_id" required>
        <option value="">Pilih kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                @selected((string) old('category_id', $ticket->category_id) === (string) $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</label></p>

@if (! $ticket->exists)
    <p><label>Pemilik (demo lokal)
        <select name="user_id" required>
            <option value="">Pilih pemilik</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}"
                    @selected((string) old('user_id') === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
    </label></p>
@else
    <p><label>Status
        <select name="status" required>
            @foreach (['open', 'pending', 'closed'] as $status)
                <option value="{{ $status }}"
                    @selected(old('status', $ticket->status) === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </select>
    </label></p>
@endif

<input type="hidden" name="is_urgent" value="0">
<label><input type="checkbox" name="is_urgent" value="1"
    @checked((bool) old('is_urgent', $ticket->is_urgent))> Urgent</label>

<p><label>Catatan {{ $ticket->exists ? 'perubahan' : 'awal' }}
    <textarea name="note" required maxlength="1000">{{ old('note') }}</textarea>
</label></p>
<button type="submit">Simpan</button>
