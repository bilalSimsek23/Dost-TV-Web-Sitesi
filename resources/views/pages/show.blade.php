@extends('layouts.app')

@section('title', $page->title . ' - Dost TV')

@section('content')
    <div class="py-8">
        <x-site.page-card :title="$page->title" :content="$page->content" />
    </div>
@endsection
