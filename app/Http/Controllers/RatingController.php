<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $authors = Author::all()->sortBy('name');

        $books = collect();

        // Jika ada author dipilih, ambil buku dari relasi
        if ($request->has('author') && $request->author) {
            $author = $authors->firstWhere('id', $request->author);
            if ($author) {
                $books = $author->books->sortBy('title'); // Eloquent relation
            }
        }

        return view('ratings.create', compact('authors', 'books'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'name' => 'required|max:50',
            'rating' => 'required|numeric|min:1|max:10',
        ], [
            'book_id.required' => 'Buku harus dipilih.',
            'book_id.exists' => 'Buku tidak ditemukan.',
            'name.required' => 'Nama harus diisi.',
            'name.max' => 'Nama maksimal 50 karakter.',
            'rating.required' => 'Rating harus diisi.',
            'rating.numeric' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal 1.',
            'rating.max' => 'Rating maksimal 10.',
        ]);

        Rating::create($validated);

        return redirect()->route('index')->with('success', 'Rating berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Rating $rating)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rating $rating)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rating $rating)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rating $rating)
    {
        //
    }
}
