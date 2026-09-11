@extends('layouts.app')

@section('title', 'Dost TV - Ana Sayfa')

@section('content')
    {{-- Fixed Hero Section (Always Top) --}}
    <x-site.home.hero-section :today-schedule="$todaySchedule" :hero-programs="$heroPrograms" :block="$fixedSettings['hero'] ?? []" />

    {{-- Dynamic Builder Blocks (Between Hero and Footer) --}}
    <x-site.homepage-sections
        :sections="$homepageSections"
        :banners="$banners"
        :settings="$settings"
        :today-schedule="$todaySchedule"
        :featured-programs="$featuredPrograms"
        :hero-programs="$heroPrograms"
        :resolved-block-data="$resolvedBlockData ?? []"
    />
@endsection
