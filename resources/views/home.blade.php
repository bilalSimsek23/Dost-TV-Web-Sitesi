@extends('layouts.app')

@section('title', 'Dost TV - Ana Sayfa')

@section('content')
    <x-site.homepage-sections
        :sections="$homepageSections"
        :banners="$banners"
        :settings="$settings"
        :today-schedule="$todaySchedule"
        :featured-programs="$featuredPrograms"
    />
@endsection
