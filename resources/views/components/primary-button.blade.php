<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-success w-100']) }}>
    {{ $slot }}
</button>
