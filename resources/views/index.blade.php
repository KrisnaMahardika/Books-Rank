@extends('layouts.app')
@section('content')
<h1>Data Buku</h1>

<!-- Filter -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form action="{{ url()->current() }}" method="GET">
            <h5 class="card-title mb-3">Filter Buku</h5>
            <div class="row g-3">

                <!-- Category Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Kategori</label>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary w-100 text-start dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Pilih kategori
                        </button>
                        <div class="dropdown-menu p-3" aria-labelledby="categoryDropdown" style="max-height: 300px; overflow-y: auto; width: 100%;">
                            <div id="categoryList">
                                @foreach ($categories as $category)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="category_{{$category->id}}" name="categories[]" value="{{$category->id}}" {{ in_array($category->id, request('categories', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="category_{{$category->id}}">{{$category->name}}</label>
                                </div>
                                @endforeach
                                <!-- Tambahkan kategori lain di sini -->
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Pilih satu atau lebih kategori</small>
                </div>


                <!-- Author Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Penulis</label>
                    <select class="form-select" name="author">
                        <option value="">Semua</option>
                        @foreach ($authors as $author)
                        <option value="{{$author->id}}" {{ request('author') == $author->id ? 'selected' : '' }}>{{$author->name}}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Rating Range -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Rentang Rating</label>
                    <div class="d-flex gap-2">
                        <input type="number" step="0.1" min="1" max="10" class="form-control" name="rating_from" placeholder="Min" value="{{ request('rating_from') }}">
                        <input type="number" step="0.1" min="1" max="10" class="form-control" name="rating_to" placeholder="Maks" value="{{ request('rating_to') }}">
                    </div>
                </div>

                <!-- Publication Year Range -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tahun Terbit</label>
                    <div class="d-flex gap-2">
                        <input type="number" class="form-control" name="year_from" placeholder="Dari" value="{{ request('year_from') }}">
                        <input type="number" class="form-control" name="year_to" placeholder="Sampai" value="{{ request('year_to') }}">
                    </div>
                </div>

                <!-- Store Location -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Lokasi Toko</label>
                    <select class="form-select" name="store_location">
                        <option value="">Semua</option>
                        @foreach ($storeLocations as $location)
                        <option value="{{$location}}" {{ request('store_location') == $location ? 'selected' : '' }}>{{$location}}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Availability Status -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="rented" {{ request('status') == 'rented' ? 'selected' : '' }}>Rented</option>
                        <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                    </select>
                </div>


                <!-- sort -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort by</label>
                    <select class="form-select" name="sort">
                        <option value="rating" {{ request('sort', 'rating') == 'rating' ? 'selected' : '' }}>Weighted average rating (default)</option>
                        <option value="totalVote" {{ request('sort') == 'totalVote' ? 'selected' : '' }}> Total Vote</option>
                        <option value="popularity" {{ request('sort') == 'popularity' ? 'selected' : '' }}> Popularity (Last 30 days)
                        <option <input type="radio" name="sort" value="alphabetical" {{ request('sort') == 'alphabetical' ? 'selected' : '' }}> Alphabetical</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-md-3 d-flex gap-2 align-items-end justify-content-end ms-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Terapkan Filter
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-secondary">
                        Reset
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- table buku -->
<table class="table">
    <thead>
        <tr>
            <th>No</th>
            <th>Title</th>
            <th>Category</th>
            <th>Author</th>
            <th>Rating</th>
            <th>Total Rate</th>
            <th>ISBN</th>
            <th>Publisher</th>
            <th>Publication Year</th>
            <th>Store Location</th>
            <th>Trend</th>
            <th>Status</th>
            <!-- <th>Action</th> -->
        </tr>
    </thead>
    <tbody>
        @foreach ($books as $book)
        <tr>
            <td>{{$loop->iteration + ($books->firstItem() - 1)}}</td>
            <td>{{$book -> title}}</td>
            <td>{{$book -> category -> name}}</td>
            <td>{{$book -> author -> name}}</td>
            <td>{{$book -> avgRating ?? '-'}}</td>
            <td>{{$book -> totalRating ?? '-'}}</td>
            <td>{{$book -> isbn}}</td>
            <td>{{$book -> publisher}}</td>
            <td>{{$book -> publication_year}}</td>
            <td>{{$book -> store_location}}</td>
            <td class="text-center">
                @if ($book -> trend === 'up')
                <span class="text-success fw-bold" title="Rating meningkat 7 hari terakhir">↑</span>
                @elseif ($book -> trend === 'down')
                <span class="text-danger fw-bold" title="Rating menurun 7 hari terakhir">↓</span>
                @else
                <span class="text-secondary" title="Stabil">–</span>
                @endif
            </td>
            <td>
                @switch($book -> status)
                @case('reserved')
                <span class="badge bg-warning">Reserved</span>
                @break
                @case('rented')
                <span class="badge bg-danger">Rented</span>
                @break
                @default
                <span class="badge bg-success">Available</span>
                @endswitch
            </td>
            <!-- <td><a href="" class="btn btn-outline-primary">Rate</a></td> -->
        </tr>
        @endforeach
    </tbody>
</table>

{{$books->links()}}
@endsection