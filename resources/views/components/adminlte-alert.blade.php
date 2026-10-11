@props([
    'theme' => 'danger',
    'title' => null,
    'icon' => 'fas fa-ban',
    'dismissable' => false,
])

<div {{ $attributes->merge(['class' => "alert alert-{$theme}" . ($dismissable ? ' alert-dismissible' : '') . ' shadow-sm']) }} role="alert">
    @if($dismissable)
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    @endif
    
    @if($title || $icon)
        <h5>
            @if($icon)
                <i class="icon {{ $icon }} mr-1"></i>
            @endif
            {{ $title }}
        </h5>
    @endif

    {{ $slot }}
</div>
