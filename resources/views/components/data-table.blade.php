@props([
    'columns' => [],
    'data' => [],
    'actions' => true,
    'showCheckbox' => false,
    'selectable' => false
])

<div class="responsive-table-container">
    <table class="responsive-table min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @if($showCheckbox && $selectable)
                    <th class="w-12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                               x-data
                               @change="
                                   const checkboxes = document.querySelectorAll('tbody input[type=checkbox]');
                                   checkboxes.forEach(cb => cb.checked = $event.target.checked);
                               ">
                    </th>
                @endif
                
                @foreach($columns as $column)
                    <th class="{{ $column['width'] ?? 'w-auto' }} px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $column['label'] }}
                    </th>
                @endforeach
                
                @if($actions)
                    <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($data as $index => $item)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    @if($showCheckbox && $selectable)
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                   value="{{ $item['id'] ?? $index }}">
                        </td>
                    @endif
                    
                    @foreach($columns as $column)
                        <td class="{{ $column['width'] ?? 'w-auto' }} px-6 py-4">
                            <div class="min-w-0">
                                @if(isset($column['render']))
                                    {!! $column['render']($item, $index) !!}
                                @elseif(isset($column['field']))
                                    <div class="text-sm text-gray-900 truncate">
                                        {{ data_get($item, $column['field']) }}
                                    </div>
                                @else
                                    <div class="text-sm text-gray-900 truncate">
                                        {{ $item }}
                                    </div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                    
                    @if($actions)
                        <td class="w-32 px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                @if(isset($item['actions']))
                                    @foreach($item['actions'] as $action)
                                        @if($action['type'] === 'link')
                                            <a href="{{ $action['url'] }}" 
                                               class="text-{{ $action['color'] ?? 'blue' }}-600 hover:text-{{ $action['color'] ?? 'blue' }}-900 transition-colors duration-150">
                                                {{ $action['label'] }}
                                            </a>
                                        @elseif($action['type'] === 'button')
                                            <button type="button" 
                                                    class="text-{{ $action['color'] ?? 'blue' }}-600 hover:text-{{ $action['color'] ?? 'blue' }}-900 transition-colors duration-150"
                                                    @if(isset($action['onclick']))
                                                        onclick="{{ $action['onclick'] }}"
                                                    @endif>
                                                {{ $action['label'] }}
                                            </button>
                                        @elseif($action['type'] === 'form')
                                            <form action="{{ $action['url'] }}" method="{{ $action['method'] ?? 'POST' }}" class="inline">
                                                @csrf
                                                @if($action['method'] !== 'POST')
                                                    @method($action['method'])
                                                @endif
                                                <button type="submit" 
                                                        class="text-{{ $action['color'] ?? 'red' }}-600 hover:text-{{ $action['color'] ?? 'red' }}-900 transition-colors duration-150"
                                                        @if(isset($action['confirm']))
                                                            onclick="return confirm('{{ $action['confirm'] }}')"
                                                        @endif>
                                                    {{ $action['label'] }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                @elseif(isset($item['approval_request_id']) && isset($item['master_item_id']))
                                    <button type="button" 
                                            class="text-emerald-600 hover:text-emerald-900 transition-colors duration-150"
                                            onclick="resolveAndOpen('{{ $item['approval_request_id'] }}', '{{ $item['master_item_id'] }}', '{{ addslashes(($item['no_input'] ?? '-') . ' • ' . ($item['jenis'] ?? '-') . ' • QTY ' . ($item['qty'] ?? '')) }}')">
                                        Proses
                                    </button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($showCheckbox ? 1 : 0) + ($actions ? 1 : 0) }}" 
                        class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-lg font-medium">Belum ada data</p>
                            <p class="text-sm">Data akan muncul di sini setelah ditambahkan</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
