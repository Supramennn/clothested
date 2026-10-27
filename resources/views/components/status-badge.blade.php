@props(['status'])

@php
    $styles = [
        'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
        'processing' => 'border-sky-200 bg-sky-50 text-sky-800',
        'shipped' => 'border-stone-200 bg-stone-100 text-stone-700',
        'ready_for_pickup' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'completed' => 'border-stone-900 bg-stone-900 text-white',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700',
    ];

    $label = ucfirst(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold '.($styles[$status] ?? 'border-stone-200 bg-stone-100 text-stone-700')]) }}>
    {{ $label }}
</span>
