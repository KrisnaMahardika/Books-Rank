@extends('layouts.app')
@section('content')
<a href="{{ route('index') }}" class="btn btn-secondary">Kembali</a>

<div class="card mb-4 mt-4 shadow-sm">
    <div class="card-body">
        {{-- FORM FILTER (GET) --}}
        <form action="{{ route('ratings.create') }}" method="GET">
            <h5 class="card-title mb-3">Tambah Rating</h5>
            <div class="row g-3">

                <!-- Author -->
                <div>
                    <label class="form-label fw-semibold">Penulis</label>
                    <select class="form-select" name="author" onchange="this.form.submit()">
                        <option value="">Pilih Author</option>
                        @foreach ($authors as $author)
                        <option value="{{ $author->id }}" {{ request('author') == $author->id ? 'selected' : '' }}>
                            {{ $author->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                @if (request('author'))
                <!-- Buku -->
                <div>
                    <label class="form-label fw-semibold">Buku</label>
                    <select class="form-select" name="book_id" onchange="this.form.submit()">
                        <option value="">Pilih Buku</option>
                        @foreach ($books as $book)
                        <option value="{{ $book->id }}" {{ request('book_id') == $book->id ? 'selected' : '' }}>
                            {{ $book->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

            </div>
        </form>

        {{-- FORM INPUT RATING (POST) --}}
        @if (request('book_id'))
        <form action="{{ route('ratings.store') }}" method="POST" class="mt-4">
            @csrf
            <input type="hidden" name="book_id" value="{{ request('book_id') }}">

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Nama</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    placeholder="Input Nama">
                @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="rating" class="form-label fw-semibold">Rating</label>
                <input type="number" name="rating" class="form-control @error('rating') is-invalid @enderror"
                    placeholder="Input Rating: 1 - 10" min="1" max="10" step="0.1">
                @error('rating')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Rating
            </button>
        </form>
        @endif
    </div>
</div>
@endsection