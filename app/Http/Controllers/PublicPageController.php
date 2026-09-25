<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PublicPageController extends Controller
{
    private function shared(): array
    {
        return [
            'settings' => SiteSetting::first(),
            'blocks' => ContentBlock::all()->keyBy('key'),
        ];
    }

    public function home(): View
    {
        return view('pages.home', [
            ...$this->shared(),
            'featuredProducts' => Product::where('is_published', true)
                ->orderByDesc('is_featured')->orderBy('sort_order')->limit(2)->get(),
        ]);
    }

    public function about(): View
    {
        return view('pages.about', $this->shared());
    }

    public function products(): View
    {
        return view('pages.products', [
            ...$this->shared(),
            'products' => Product::where('is_published', true)->orderBy('sort_order')->paginate(9),
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_published, 404);

        return view('pages.product', [...$this->shared(), 'product' => $product]);
    }

    public function blog(): View
    {
        return view('pages.blog', [
            ...$this->shared(),
            'articles' => Article::where('is_published', true)
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderByDesc('published_at')->orderByDesc('id')->paginate(9),
        ]);
    }

    public function article(Article $article): View
    {
        abort_unless($article->is_published && (! $article->published_at || $article->published_at->isPast()), 404);

        return view('pages.article', [
            ...$this->shared(),
            'article' => $article,
            'articleHtml' => Str::markdown($article->body, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', $this->shared());
    }
}
