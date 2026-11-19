@props(['icon' => 'fas fa-seedling', 'title', 'description'])
<div class="empty-state"><span class="empty-icon"><i class="{{ $icon }}" aria-hidden="true"></i></span><h3>{{ $title }}</h3><p>{{ $description }}</p></div>
