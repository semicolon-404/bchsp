<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The blog as visitors read it: the list of articles, and one article. */
class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        $category = is_string($request->query('category'))
            ? BlogCategory::where('slug', $request->query('category'))->first()
            : null;

        return view('blog.index', [
            'category' => $category,
            // Only the categories that have something out to read.
            'categories' => BlogCategory::whereHas('posts', fn ($query) => $query->published())->orderBy('sort_order')->orderBy('name')->get(),
            'posts' => BlogPost::published()
                ->with('category')
                ->when($category, fn ($query) => $query->where('blog_category_id', $category->id))
                ->latest('published_at')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function show(BlogPost $post): View
    {
        // Staff who are signed in can read an article before it is out, to check it.
        abort_unless($post->isPublished() || auth()->check(), 404);

        return view('blog.show', [
            'post' => $post->load('category'),
            'more' => BlogPost::published()->with('category')->whereKeyNot($post->id)->latest('published_at')->limit(3)->get(),
        ]);
    }
}
