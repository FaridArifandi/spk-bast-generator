@props(['s'])
@php($e = $s instanceof \App\Enums\StatusVerifikasi ? $s : \App\Enums\StatusVerifikasi::tryFrom((string) $s))
@if($e)<span class="badge {{ $e->badge() }}">{{ $e->label() }}</span>@endif
