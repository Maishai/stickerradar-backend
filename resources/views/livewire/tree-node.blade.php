<li class="relative pl-5">
    <div class="inline-block px-3 py-1 mb-1 bg-gray-100 border-4 rounded-md text-sm text-black font-medium transition-all duration-300"
         style="border-color: {{ $node['color'] ?? '#ccc' }};">
        {{ $node['name'] }}
    </div>
    @if (isset($node['children']) && count($node['children']) > 0)
        <ul class="list-none pl-5 ml-2">
            @foreach ($node['children'] as $child)
                @include('livewire.tree-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
    
    <!-- Linien für Baumstruktur -->
    <span class="absolute top-2.5 left-0 h-full w-px bg-gray-400"></span> <!-- Vertikale Linie -->
    <span class="absolute top-2.5 left-0 w-2.5 h-px bg-gray-400"></span> <!-- Horizontale Linie -->
</li>