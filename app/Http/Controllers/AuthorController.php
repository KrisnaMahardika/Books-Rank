<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Cache hasil 30 menit agar tidak query berulang
        $authors = Cache::remember('top_authors_stats', now()->addMinutes(30), function () {

            // === total voters (rating > 5) ===
            $popularitySub = Rating::query()
                ->join('books', 'books.id', '=', 'ratings.book_id')
                ->select('books.author_id', DB::raw('COUNT(ratings.id) as voters_count'))
                ->where('ratings.rating', '>', 5)
                ->groupBy('books.author_id');

            // average rating keseluruhan
            $avgSub = Rating::query()
                ->join('books', 'books.id', '=', 'ratings.book_id')
                ->select('books.author_id', DB::raw('AVG(ratings.rating) as avg_rating'))
                ->groupBy('books.author_id');
            
            // average rating 30 hari terakhir
            $recentSub = Rating::query()
                ->join('books', 'books.id', '=', 'ratings.book_id')
                ->select('books.author_id', DB::raw('AVG(ratings.rating) as recent_avg'))
                ->where('ratings.created_at', '>=', now()->subDays(30))
                ->groupBy('books.author_id');

            // average rating 31–60 hari sebelumnya
            $previousSub = Rating::query()
                ->join('books', 'books.id', '=', 'ratings.book_id')
                ->select('books.author_id', DB::raw('AVG(ratings.rating) as previous_avg'))
                ->whereBetween('ratings.created_at', [now()->subDays(60), now()->subDays(31)])
                ->groupBy('books.author_id');

            // === Gabungkan semua subquery ke tabel authors ===
            $authors = Author::query()
                ->leftJoinSub($popularitySub, 'pop', 'pop.author_id', '=', 'authors.id')
                ->leftJoinSub($avgSub, 'avg', 'avg.author_id', '=', 'authors.id')
                ->leftJoinSub($recentSub, 'recent', 'recent.author_id', '=', 'authors.id')
                ->leftJoinSub($previousSub, 'prev', 'prev.author_id', '=', 'authors.id')
                ->select(
                    'authors.*',
                    DB::raw('COALESCE(pop.voters_count, 0) as voters_count'),
                    DB::raw('ROUND(COALESCE(avg.avg_rating, 0), 2) as avg_rating'),
                    DB::raw('ROUND(COALESCE(recent.recent_avg, 0), 2) as recent_avg'),
                    DB::raw('ROUND(COALESCE(prev.previous_avg, 0), 2) as previous_avg'),
                    DB::raw('ROUND((COALESCE(recent.recent_avg, 0) - COALESCE(prev.previous_avg, 0)) * COALESCE(pop.voters_count, 1), 2) as trending_score')
                )
                ->with([
                    // ambil hanya relasi buku yang dibutuhkan untuk best/worst
                    'books' => function ($query) {
                        $query->select('id', 'author_id', 'title')
                            ->withAvg('ratings', 'rating');
                    }
                ])
                ->get();

            // === Ambil best & worst book via collection===
            $authors->transform(function ($author) {
                $books = $author->books;

                if ($books->isNotEmpty()) {
                    $bestBook = $books->sortByDesc('ratings_avg_rating')->first();
                    $worstBook = $books->sortBy('ratings_avg_rating')->first();

                    $author->best_book = $bestBook?->title;
                    $author->worst_book = $worstBook?->title;
                } else {
                    $author->best_book = null;
                    $author->worst_book = null;
                }

                return $author;
            });

            return $authors;
        });

        // === Sort ===
        $sort = $request->get('sort', 'popularity');
        if ($sort === 'average') {
            $authors = $authors->sortByDesc('avg_rating');
        } elseif ($sort === 'trending') {
            $authors = $authors->sortByDesc('trending_score');
        } else {
            $authors = $authors->sortByDesc('voters_count');
        }

        // ambil 20 authors
        $authors = $authors->take(20);

        return view('authors.index', compact('authors', 'sort'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Author $author)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Author $author)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Author $author)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Author $author)
    {
        //
    }
}
