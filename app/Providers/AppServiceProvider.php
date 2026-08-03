<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'page' => Page::class,
            'program' => Program::class,
            'category' => Category::class,
        ]);

        View::composer('layouts.app', function ($view) {
            $view->with(
                'menuPages',
                Page::where('show_in_menu', true)->orderBy('sort_order')->get()
            );
        });
    }
}
