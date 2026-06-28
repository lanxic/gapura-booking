@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="fw-bold mb-4">{{ $title }}</h2>
            <div class="card">
                <div class="card-body prose">
                    @if($content)
                        {!! $content !!}
                    @else
                        <p class="text-muted">Konten belum tersedia.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
