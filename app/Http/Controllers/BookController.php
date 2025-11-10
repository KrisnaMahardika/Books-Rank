<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();

        // Base query buku
        $books = Book::query()
            ->select('books.*')
            ->addSelect([
                // total ratings
                'ratings_count' => Rating::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ratings.book_id', 'books.id'),

                // avg rating
                'ratings_avg_rating' => Rating::query()
                    ->selectRaw('AVG(rating)')
                    ->whereColumn('ratings.book_id', 'books.id'),

                // recent popularity (30 hari terakhir)
                'recent_popularity' => Rating::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ratings.book_id', 'books.id')
                    ->where('ratings.created_at', '>=', $now->copy()->subDays(30)),

                // recent avg rating (7 hari terakhir)
                'recent_avg_rating' => Rating::query()
                    ->selectRaw('AVG(rating)')
                    ->whereColumn('ratings.book_id', 'books.id')
                    ->where('ratings.created_at', '>=', $now->copy()->subDays(7)),
            ])
            ->with(['category:id,name', 'author:id,name']); // hanya ambil kolom penting

        //  FILTERS
        $books
            ->when($request->filled('categories'), function ($query) use ($request) {
                $query->whereIn('category_id', $request->categories);
            })
            ->when($request->filled('author'), function ($query) use ($request) {
                $query->where('author_id', $request->author);
            })
            ->when($request->filled('store_location'), function ($query) use ($request) {
                $query->where('store_location', $request->store_location);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('year_from'), function ($query) use ($request) {
                $query->where('publication_year', '>=', $request->year_from);
            })
            ->when($request->filled('year_to'), function ($query) use ($request) {
                $query->where('publication_year', '<=', $request->year_to);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%$search%")
                        ->orWhere('isbn', 'like', "%$search%")
                        ->orWhere('publisher', 'like', "%$search%")
                        ->orWhereHas('author', fn($a) => $a->where('name', 'like', "%$search%"));
                });
            });

        // SORTING
        // Cache rata-rata global
        $chaceAvgRating = Cache::remember('global_avg_rating', now()->addMinutes(30), function () {
            return round(Rating::avg('rating'), 2);
        });
        $minRating = 5; // minimum threshold
        $sort = $request->get('sort', 'rating');

        if ($sort === 'alphabetical') {
            $books->orderBy('title');
        } elseif ($sort === 'totalVote') {
            $books->orderByDesc('ratings_count');
        } elseif ($sort === 'popularity') {
            $books->orderByDesc('recent_popularity');
        } else {
            // Weighted rating (dihitung langsung di query)
            $books->orderByRaw("
                ((ratings_count / (ratings_count + $minRating)) * ratings_avg_rating + 
                 ($minRating / (ratings_count + $minRating)) * $chaceAvgRating) DESC
            ");
        }

        // RANGE FILTER BY AVERAGE
        if ($request->filled('rating_from')) {
            $books->having('ratings_avg_rating', '>=', $request->rating_from);
        }
        if ($request->filled('rating_to')) {
            $books->having('ratings_avg_rating', '<=', $request->rating_to);
        }

        // EXECUTION
        $books = $books->paginate(100)->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $authors = Author::orderBy('name')->get(['id', 'name']);
        $storeLocations = Book::select('store_location')
            ->distinct()
            ->orderBy('store_location')
            ->pluck('store_location');

        return view('index', compact('books', 'categories', 'authors', 'storeLocations'));
    }

    // public function index(Request $request)
    // {
    //     // $books = Book::with(['category', 'author'])->withAvg('ratings', 'rating')->withCount('ratings')->withCount(['ratings as recent_popularity' => function (Builder $query) {
    //     //     $query->where('created_at', '>=', Carbon::now()->subDays(30));
    //     // }])->withAvg(['ratings as recent_avg_rating' => function (Builder $query) {
    //     //     $query->where('created_at', '>=', Carbon::now()->subDays(7));
    //     // }], 'rating');

    //     $books = Book::with(['category', 'author'])
    //         ->withAvg('ratings', 'rating')
    //         ->withCount('ratings')
    //         ->withCount([
    //             'ratings as recent_popularity' => function (Builder $query) {
    //                 $query->where('created_at', '>=', Carbon::now()->subDays(30));
    //             }
    //         ])
    //         ->withAvg([
    //             'ratings as recent_avg_rating' => function (Builder $query) {
    //                 $query->where('created_at', '>=', Carbon::now()->subDays(7));
    //             }
    //         ], 'rating');

    //     $categories = Category::all()->sortBy('name');
    //     $authors = Author::all()->sortBy('name');

    //     $storeLocations = Book::select('store_location')->distinct()->orderBy('store_location')->pluck('store_location');

    //     $selectedCategories = $request->input('categories', []);
    //     $authorId = $request->input('author');
    //     $ratingFrom = $request->input('rating_from');
    //     $ratingTo = $request->input('rating_to');
    //     $storeLoc = $request->input('store_location');
    //     $status = $request->input('status');
    //     $yearFrom = $request->input('year_from');
    //     $yearTo = $request->input('year_to');
    //     $sort = $request->input('sort', 'rating');

    //     $search = $request->input('search');

    //     if (!empty($selectedCategories)) {
    //         $books = $books->whereIn('category_id', $selectedCategories);
    //     }
    //     if ($authorId) {
    //         $books = $books->where('author_id', $authorId);
    //     }
    //     if ($ratingFrom) {
    //         $books = $books->having('ratings_avg_rating', '>=', $ratingFrom);
    //     }
    //     if ($ratingTo) {
    //         $books = $books->having('ratings_avg_rating', '<=', $ratingTo);
    //     }
    //     if ($storeLoc) {
    //         $books = $books->where('store_location', $storeLoc);
    //     }
    //     if ($status) {
    //         $books = $books->where('status', $status);
    //     }
    //     if ($yearFrom) {
    //         $books = $books->where('publication_year', '>=',  $yearFrom);
    //     }
    //     if ($yearTo) {
    //         $books = $books->where('publication_year', '<=', $yearTo);
    //     }

    //     if ($search) {
    //         $books = $books->where('title', 'like', "%{$search}%")
    //         ->orWhere('isbn', 'like', "%{$search}%")
    //         ->orWhere('publisher', 'like', "%{$search}%")
    //         ->orWhereHas('author', function(Builder $query) use($search) {$query->where('name', 'like', "%{$search}%");});
    //     }

    //     // Menghitung WeiWeighted Rating
    //     $C = DB::table('ratings')->avg('rating');
    //     $m = 5; // threshold minimum rating

    //     if ($sort === 'alphabetical') {
    //         $books = $books->orderBy('title');
    //     } else if ($sort === 'totalVote') {
    //         $books = $books->orderByDesc('ratings_count');
    //     } else if ($sort == 'popularity') {
    //         $books = $books->orderByDesc('recent_popularity');
    //     } else {
    //         // $books = $books->orderByDesc('ratings_avg_rating');
    //         $books = $books->orderByRaw("((ratings_count/(ratings_count + $m)) * ratings_avg_rating + ($m/(ratings_count + $m)) * $C) DESC");
    //     }

    //     $books = $books->paginate(100)->withQueryString();

    //     return view('index', compact('books', 'categories', 'authors', 'storeLocations'));
    // }

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
    public function show(Book $book)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        //
    }
}
