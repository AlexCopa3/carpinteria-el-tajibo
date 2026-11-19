@props(['title', 'subtitle' => null, 'eyebrow' => null, 'refined' => false])
<div @class(['page-heading', 'page-heading-refined' => $refined])>
    <div>@if($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif<h1>{{ $title }}</h1>@if($subtitle)<p class="page-subtitle">{{ $subtitle }}</p>@endif</div>
    @if(!$slot->isEmpty())<div class="page-actions">{{ $slot }}</div>@endif
</div>
