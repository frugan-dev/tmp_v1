@extends('layouts.maintenance')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen bg-[radial-gradient(circle_at_center,white_0%,white_10%,var(--color-light-gray)_50%)] text-center py-6">
    <h1>Dissipatore alimentare.</h1>
    <h2 class="h1">Semplicemente, <span class="text-primary">Gully.</span></h2>

    <div class="w-full h-120 md:max-w-4xl md:h-auto my-6 overflow-hidden">
        <img class="h-full w-auto object-cover md:w-full md:h-auto" src="{{ Vite::asset('resources/assets/img/maintenance/gully.jpg') }}" alt="">
    </div>

    <p class="text-h3">Facile da installare e da usare.</p>
    <p class="h3">Si adatta ad ogni tipo di lavello.</p>
</div>
@endsection
