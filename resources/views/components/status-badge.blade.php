@props(['status'])

@php
    $styles = [
        'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
        'processing' => 'border-aloe bg-aloe text-black',
        'shipped' => 'border-zinc-200 bg-zinc-100 text-zinc-700',
        'ready_for_pickup' => 'border-pistachio bg-pistachio text-black',
        'completed' => 'border-black bg-black text-white',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700',
    ];

    $label = ucfirst(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium '.($styles[$status] ?? 'border-zinc-200 bg-zinc-100 text-zinc-700')]) }}>
    {{ $label }}
</span>
