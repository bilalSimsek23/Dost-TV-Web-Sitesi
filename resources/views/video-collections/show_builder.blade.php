@extends('layouts.app')

@section('title', ($collection->name ?? 'Video Koleksiyonu') . ' - Dost TV')

@section('content')
    <x-site.video-collection-sections
        :sections="$sections"
        :collection="$collection"
        :episodes="$episodes"
        :settings="$settings"
        :preview="$preview ?? false"
    />
@endsection
