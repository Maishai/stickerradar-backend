<li class="tree-node">
    <div class="node-content" style="border-color: {{ $node['color'] ?? '#ccc' }}; background-color: #f9f9f9; border-width: 4px;">
        {{ $node['name'] }}
    </div>
    @if (isset($node['children']) && count($node['children']) > 0)
        <ul>
            @foreach ($node['children'] as $child)
                @include('livewire.tree-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>