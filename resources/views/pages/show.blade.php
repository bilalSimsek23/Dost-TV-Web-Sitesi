@extends('layouts.app')

@section('title', $page->title . ' - Dost TV')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-black text-white">{{ $page->title }}</h1>
        <div class="prose prose-invert mt-8 max-w-none prose-headings:text-white prose-a:text-rose-400">
            {!! $page->content !!}
        </div>
    </section>
@endsection
