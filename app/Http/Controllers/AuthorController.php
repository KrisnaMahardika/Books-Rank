<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Rating;
use Carbon\Carbon;
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
        // Gunakan caching untuk menghindari query berat berulang
        // $authors = Cache::remember('top_authors_stats', now()->addMinutes(30), function () {
        //     // Subquery: total voters (rating > 5)
        //     $popularitySub = DB::table('ratings')
        //         ->join('books', 'books.id', '=', 'ratings.book_id')
        //         ->select('books.author_id', DB::raw('COUNT(ratings.id) as voters_count'))
        //         ->where('ratings.rating', '>', 5)
        //         ->groupBy('books.author_id');

        //     // Subquery: average rating keseluruhan
        //     $avgSub = DB::table('ratings')
        //         ->join('books', 'books.id', '=', 'ratings.book_id')
        //         ->select('books.author_id', DB::raw('AVG(ratings.rating) as avg_rating'))
        //         ->groupBy('books.author_id');

        //     // Subquery: average rating 30 hari terakhir
        //     $recentSub = DB::table('ratings')
        //         ->join('books', 'books.id', '=', 'ratings.book_id')
        //         ->select('books.author_id', DB::raw('AVG(ratings.rating) as recent_avg'))
        //         ->whereBetween('ratings.created_at', [now()->subDays(30), now()])
        //         ->groupBy('books.author_id');

        //     // Subquery: average rating 31–60 hari sebelumnya
        //     $previousSub = DB::table('ratings')
        //         ->join('books', 'books.id', '=', 'ratings.book_id')
        //         ->select('books.author_id', DB::raw('AVG(ratings.rating) as previous_avg'))
        //         ->whereBetween('ratings.created_at', [now()->subDays(60), now()->subDays(31)])
        //         ->groupBy('books.author_id');

        //     // Gabungkan semua subquery ke tabel authors
        //     $query = Author::query()
        //         ->leftJoinSub($popularitySub, 'pop', 'pop.author_id', '=', 'authors.id')
        //         ->leftJoinSub($avgSub, 'avg', 'avg.author_id', '=', 'authors.id')
        //         ->leftJoinSub($recentSub, 'recent', 'recent.author_id', '=', 'authors.id')
        //         ->leftJoinSub($previousSub, 'prev', 'prev.author_id', '=', 'authors.id')
        //         ->select(
        //             'authors.*',
        //             DB::raw('COALESCE(pop.voters_count, 0) as voters_count'),
        //             DB::raw('ROUND(COALESCE(avg.avg_rating, 0), 2) as avg_rating'),
        //             DB::raw('ROUND(COALESCE(recent.recent_avg, 0), 2) as recent_avg'),
        //             DB::raw('ROUND(COALESCE(prev.previous_avg, 0), 2) as previous_avg'),
        //             DB::raw('ROUND((COALESCE(recent.recent_avg, 0) - COALESCE(prev.previous_avg, 0)) * COALESCE(pop.voters_count, 1), 2) as trending_score')
        //         );

        //     // Ambil hasil mentah dulu
        //     $authors = $query->get();

        //     // Tambahkan best/worst book untuk tiap author (query ringan karena per-author)
        //     foreach ($authors as $author) {
        //         $bestBook = DB::table('books')
        //             ->join('ratings', 'ratings.book_id', '=', 'books.id')
        //             ->where('books.author_id', $author->id)
        //             ->select('books.title', DB::raw('AVG(ratings.rating) as avg'))
        //             ->groupBy('books.id', 'books.title')
        //             ->orderByDesc('avg')
        //             ->first();

        //         $worstBook = DB::table('books')
        //             ->join('ratings', 'ratings.book_id', '=', 'books.id')
        //             ->where('books.author_id', $author->id)
        //             ->select('books.title', DB::raw('AVG(ratings.rating) as avg'))
        //             ->groupBy('books.id', 'books.title')
        //             ->orderBy('avg')
        //             ->first();

        //         $author->best_book = $bestBook->title ?? null;
        //         $author->worst_book = $worstBook->title ?? null;
        //     }

        //     return $authors;
        // });

        $now = Carbon::now();

        // Cache rata-rata rating global 
        $avgRating = Cache::remember('global_avg_rating', now()->addMinutes(30), function () {
            return round(Rating::avg('rating'), 2);
        });

        // Query utama menggunakan Eloquent
        $authorsQuery = Author::withCount([
            // total voters (rating > 5)
            'ratings as voters_count' => function ($query) {
                $query->where('rating', '>', 5);
            },

            // total semua rating
            'ratings as total_ratings_count',

            // popularitas terbaru (jumlah rating dalam 30 hari)
            'ratings as recent_popularity' => function ($query) use ($now) {
                $query->where('ratings.created_at', '>=', $now->copy()->subDays(30));
            },
        ])
            ->withAvg([
                // rata-rata rating keseluruhan
                'ratings as avg_rating' => function ($query) {
                    $query->whereNotNull('rating');
                },

                // rata-rata 30 hari terakhir
                'ratings as recent_avg' => function ($query) use ($now) {
                    $query->where('ratings.created_at', '>=', $now->copy()->subDays(30));
                },

                // rata-rata 31–60 hari sebelumnya
                'ratings as previous_avg' => function ($query) use ($now) {
                    $query->whereBetween('ratings.created_at', [
                        $now->copy()->subDays(60),
                        $now->copy()->subDays(31)
                    ]);
                },
            ], 'rating')
            ->with([
                'books' => function ($query) {
                    $query->select('id', 'author_id', 'title')
                        ->withAvg('ratings', 'rating');
                }
            ]);

        // Jalankan query
        $authors = $authorsQuery->get();

        // Transform data
        $authors->transform(function ($author) {
            $author->avg_rating = $author->avg_rating ? number_format($author->avg_rating, 1) : 0;
            $author->recent_avg = $author->recent_avg ? number_format($author->recent_avg, 1) : 0;
            $author->previous_avg = $author->previous_avg ? number_format($author->previous_avg, 1) : 0;

            // Trending score = selisih rata-rata * jumlah voters
            $diff = $author->recent_avg - $author->previous_avg;
            $author->trending_score = round($diff * ($author->voters_count ?: 1), 2);

            // Tentukan buku terbaik & terburuk berdasarkan rata-rata rating
            $sortedBooks = $author->books->sortByDesc('ratings_avg_rating');
            $author->best_book = optional($sortedBooks->first())->title;
            $author->worst_book = optional($sortedBooks->last())->title;

            return $author;
        });

        // Sorting tab
        $sort = $request->get('sort', 'popularity');
        switch ($sort) {
            case 'average':
                $authors = $authors->sortByDesc('avg_rating');
                break;
            case 'trending':
                $authors = $authors->sortByDesc('trending_score');
                break;
            default:
                $authors = $authors->sortByDesc('voters_count');
                break;
        }

        // Ambil top 20 penulis
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
