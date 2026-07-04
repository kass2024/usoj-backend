@props(['layout' => 'horizontal', 'showMotto' => true])

@php
    $layoutClass = match ($layout) {
        'stacked'  => 'usj-brand--stacked',
        'compact'  => 'usj-brand--compact',
        default    => 'usj-brand--horizontal',
    };
@endphp

<div {{ $attributes->merge(['class' => 'usj-brand ' . $layoutClass]) }}>
    <div class="usj-brand__crest-wrap">
        <img src="{{ asset('images/usj-crest.png') }}"
             alt="University of Saint Joseph Mbarara crest"
             class="usj-brand__crest">
    </div>
    <div class="usj-brand__copy">
        <p class="usj-brand__uni">University of Saint Joseph</p>
        <p class="usj-brand__city">Mbarara</p>
        @if($showMotto)
            <p class="usj-brand__motto">Foster Excellence and Integrity</p>
        @endif
    </div>
</div>
