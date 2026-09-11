@extends('layouts.app')

@section('title', ($collection->name ?? 'Program Koleksiyonu') . ' - Dost TV')

@section('content')
    <x-site.program-collection-sections
        :sections="$sections"
        :collection="$collection"
        :programs="$programs"
        :settings="$settings"
        :preview="$preview ?? false"
    />
@endsection
