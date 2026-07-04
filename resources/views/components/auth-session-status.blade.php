@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-danger text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
