@extends('layouts.site')

@section('content')
    @include('public.home-hero')

    @if ($homeContent !== null)
        @include('public.home-about')
    @endif

    @if ($projects->isNotEmpty())
        @include('public.project-grid', ['home' => true])
    @endif

    @include('public.home-why')

    @if ($services !== [])
        @include('public.home-services')
    @endif

    @if ($faqs->isNotEmpty())
        @include('public.home-faq')
    @endif
@endsection

@section('dialogs')
    @if ($projects->isNotEmpty())
        @include('public.project-dialog')
    @endif
@endsection
