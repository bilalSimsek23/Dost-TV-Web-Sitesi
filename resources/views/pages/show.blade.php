@extends('layouts.app')

@section('title', $page->title . ' - Dost TV')

@section('content')
    <x-site.admin-edit-bar :page="$page" />
    @if(!empty($isPreviewActive))
        <x-site.page-preview-bar :page="$page" />
    @endif
    <div class="py-8">
        <x-site.page-card :title="$page->title" :content="$page->content" :page="$page" />
    </div>
@endsection
