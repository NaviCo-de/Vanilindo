<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'products' => Product::where('is_published', true)->orderBy('sort_order')
                ->paginate(9, ['*'], 'products_page')->withQueryString()->fragment('products'),
            'articles' => Article::where('is_published', true)
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderByRaw('COALESCE(published_at, created_at) DESC')->orderByDesc('id')->get(),
        ]);
    }

    public function about(): RedirectResponse
    {
        return redirect()->to(route('home') . '#about');
    }

    public function products(Request $request): RedirectResponse
    {
        $query = $request->has('page') ? ['products_page' => $request->query('page')] : [];

        return redirect()->to(route('home', $query) . '#products');
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_published, 404);

        return view('pages.product', [...$this->shared(), 'product' => $product]);
    }

    public function blog(): RedirectResponse
    {
        return redirect()->to(route('home') . '#blog');
    }

    public function article(Article $article): View|RedirectResponse
    {
        abort_unless($article->is_published && (! $article->published_at || $article->published_at->isPast()), 404);

        if ($url = $article->externalLink()) {
            return redirect()->away($url);
        }

        return view('pages.article', [
            ...$this->shared(),
            'article' => $article,
            'articleHtml' => Str::markdown($article->body, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }

    public function contact(): RedirectResponse
    {
        return redirect()->to(route('home') . '#contact');
    }
}
