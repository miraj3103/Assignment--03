<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('categories')
            ->orderBy('name')
            ->get();

        $booksCounts = DB::table('books')
            ->select('category_id', DB::raw('COUNT(*) as books_count'))
            ->groupBy('category_id')
            ->pluck('books_count', 'category_id');

        $categories->transform(function ($category) use ($booksCounts) {
            $category->books_count = $booksCounts[$category->id] ?? 0;
            return $category;
        });

        return view('category-list', compact('categories'));
    }

    public function create()
    {
        return view('category-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        DB::table('categories')->insert([
            'name'       => $validated['name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }
}