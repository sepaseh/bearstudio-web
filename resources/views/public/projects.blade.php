@extends('layouts.site')

@section('content')
    @include('public.archive-intro', [
        'headingId' => 'projects-title',
        'description' => 'Explore the films, animation, visual effects, and sound projects in our portfolio.',
    ])
    @include('public.project-grid', ['home' => false])
@endsection
