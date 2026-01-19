@props([
'class' => 'col-md-12',
])

<!-- Start container+row component -->
<div class="row">
    <div class="{{ $class }}">
        {{ $slot }}
    </div>
</div>