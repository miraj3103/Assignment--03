<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BookController extends Controller
{

    public function index()
    {
        $books = DB::table('books')
            ->leftJoin('authors', 'books.author_id', '=', 'authors.id')
            ->leftJoin('categories', 'books.category_id', '=', 'categories.id')
            ->select('books.*', 'authors.name as author_name', 'categories.name as category_name')
            ->orderByDesc('books.id')
            ->get();

        return view('book-list', compact('books'));
    }

    // create
    public function create()
    {
        $authors = DB::table('authors')->orderBy('name')->get();
        $categories = DB::table('categories')->orderBy('name')->get();

        return view('book-create', compact('authors', 'categories'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'isbn'        => ['required', 'string', 'max:255', 'unique:books,isbn'],
            'author_id'   => ['required', 'exists:authors,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'description' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('covers', 'public');
        }

        DB::table('books')->insert([
            'title'        => $validated['title'],
            'isbn'         => $validated['isbn'],
            'author_id'    => $validated['author_id'],
            'category_id'  => $validated['category_id'],
            'cover_image'  => $coverPath,
            'description'  => $validated['description'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book created successfully.');
    }


    public function edit(int $id)
    {
        $book = DB::table('books')->where('id', $id)->first();
        if (! $book) abort(404);

        $authors = DB::table('authors')->orderBy('name')->get();
        $categories = DB::table('categories')->orderBy('name')->get();

        return view('book-edit', compact('book', 'authors', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $book = DB::table('books')->where('id', $id)->first();

        if (! $book) {
            abort(404);
        }

        // Validation
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'isbn'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('books', 'isbn')->ignore($id),
            ],
            'author_id'   => ['required', 'exists:authors,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'description' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        $coverPath = $book->cover_image;

        if ($request->hasFile('cover_image')) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }

            $coverPath = $request->file('cover_image')->store('covers', 'public');
        }

        DB::table('books')
            ->where('id', $id)
            ->update([
                'title'        => $validated['title'],
                'isbn'         => $validated['isbn'],
                'author_id'    => $validated['author_id'],
                'category_id'  => $validated['category_id'],
                'cover_image'  => $coverPath,
                'description'  => $validated['description'] ?? null,
                'published_at' => $validated['published_at'] ?? null,
                'updated_at'   => now(),
            ]);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(int $id)
    {
        $book = DB::table('books')->where('id', $id)->first();

        if (! $book) {
            abort(404);
        }

        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }

        DB::table('books')->where('id', $id)->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }
}
