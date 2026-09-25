<?php

use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/products', [PublicPageController::class, 'products'])->name('products.index');
Route::get('/products/{product:slug}', [PublicPageController::class, 'product'])->name('products.show');
Route::get('/blog', [PublicPageController::class, 'blog'])->name('blog.index');
Route::get('/blog/{article:slug}', [PublicPageController::class, 'article'])->name('blog.show');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
