<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = DB::table('authors')
            ->orderBy('name')
            ->get();

        $booksCounts = DB::table('books')
            ->select('author_id', DB::raw('COUNT(*) as books_count'))
            ->groupBy('author_id')
            ->pluck('books_count', 'author_id');

        $authors->transform(function ($author) use ($booksCounts) {
            $author->books_count = $booksCounts[$author->id] ?? 0;
            return $author;
        });

        return view('author-list', compact('authors'));
    }

    public function create()
    {
        return view('author-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:authors,name'],
            'bio'  => ['nullable', 'string'],
        ]);

        DB::table('authors')->insert([
            'name'       => $validated['name'],
            'bio'        => $validated['bio'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('authors.index')
            ->with('success', 'Author created successfully.');
    }
}