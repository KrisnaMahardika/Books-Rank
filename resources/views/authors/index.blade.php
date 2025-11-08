@extends('layouts.app')
@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Top 20 Authors</h1>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $sort == 'popularity' ? 'active' : '' }}" href="{{ route('authors.index', ['sort' => 'popularity']) }}">By Popularity</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $sort == 'average' ? 'active' : '' }}" href="{{ route('authors.index', ['sort' => 'average']) }}">By Average Rating</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $sort == 'trending' ? 'active' : '' }}" href="{{ route('authors.index', ['sort' => 'trending']) }}">Trending</a>
        </li>
    </ul>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Author</th>
                    <th>Voters Count</th>
                    <th>Avg Rating</th>
                    <th>Recent Avg</th>
                    <th>Prev Avg</th>
                    <th>Trending Score</th>
                    <th>Trend</th>
                    <th>Best Book</th>
                    <th>Worst Book</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($authors as $author)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{ $author->name }}</td>
                    <td>{{ $author->voters_count }}</td>
                    <td>{{ $author->avg_rating }}</td>
                    <td>{{ $author->recent_avg }}</td>
                    <td>{{ $author->previous_avg }}</td>
                    <td>{{ $author->trending_score }}</td>
                    <td>
                        @if($author->recent_avg > $author->previous_avg)
                        <span class="text-success fw-bold">↑</span>
                        @elseif($author->recent_avg < $author->previous_avg)
                            <span class="text-danger fw-bold">↓</span>
                            @else
                            <span class="text-secondary">–</span>
                            @endif
                    </td>
                    <td>{{ $author->best_book ?? '-' }}</td>
                    <td>{{ $author->worst_book ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No author data found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection