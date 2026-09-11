@extends('layouts.app')

@section('title', $program->name . ' - Dost TV')
@section('description', filled($program->meta_description) ? e(trim($program->meta_description)) : \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 160))

@section('content')
    <x-site.admin-edit-bar :program="$program" />

    <x-site.program-detail-sections
        :sections="$sections"
        :program="$program"
        :has-seasons="$hasSeasons"
        :season-items="$seasonItems"
        :selected-season-item="$selectedSeasonItem"
        :episodes="$episodes"
        :featured-episode="$featuredEpisode"
        :related-programs="$relatedPrograms"
        :preview="$preview ?? false"
    />
@endsection
