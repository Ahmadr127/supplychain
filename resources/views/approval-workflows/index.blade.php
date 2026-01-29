@extends('layouts.app')

@section('title', 'Kelola Approval Workflows')

@section('content')
<x-responsive-table 
    title="Kelola Approval Workflows"
    :pagination="$workflows"
    :emptyState="$workflows->count() === 0"
    emptyMessage="Belum ada workflow"
    emptyIcon="fas fa-sitemap"
    :emptyActionRoute="route('approval-workflows.create')"
    emptyActionLabel="Tambah Workflow Pertama">
    
    <x-slot name="filters">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari nama atau tipe workflow..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div class="w-32">
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Tipe</option>
                    <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Purchase</option>
                    <option value="leave" {{ request('type') == 'leave' ? 'selected' : '' }}>Leave</option>
                    <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                    <option value="travel" {{ request('type') == 'travel' ? 'selected' : '' }}>Travel</option>
                </select>
            </div>
            <div class="w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                Filter
            </button>
        </form>
    </x-slot>

    <!-- Action Buttons -->
    <div class="p-4 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-sitemap text-blue-600"></i>
                <span class="text-sm font-medium text-gray-700">Total: {{ $workflows->total() }} workflows</span>
            </div>
            <a href="{{ route('approval-workflows.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Tambah Workflow</span>
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nama & Tipe
                    </th>
                    <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Sifat Pengadaan
                    </th>
                    <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Steps
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total Requests
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Dibuat
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($workflows as $workflow)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-1/4 px-6 py-4">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">{{ $workflow->name }}</div>
                            <div class="text-sm text-gray-500 truncate">
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-2">
                                    {{ $workflow->type }}
                                </span>
                                {{ Str::limit($workflow->description, 30) }}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($workflow->procurementType)
                            <span class="inline-block px-3 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800 whitespace-nowrap">
                                {{ $workflow->procurementType->code }}
                            </span>
                        @else
                            <span class="inline-block px-3 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                -
                            </span>
                        @endif
                    </td>
                    <td class="w-1/6 px-6 py-4">
                        <div class="min-w-0">
                            <div class="flex items-center">
                                @php
                                    $steps = $workflow->steps ?? collect($workflow->workflow_steps ?? []);
                                    $stepCount = is_countable($steps) ? count($steps) : 0;
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                    {{ $stepCount }} steps
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 truncate">
                                @foreach($steps as $index => $step)
                                    @php
                                        $stepData = is_object($step) ? $step : (object) $step;
                                        $stepName = $stepData->step_name ?? $stepData->name ?? 'Step ' . ($index + 1);
                                        $stepType = $stepData->step_type ?? 'approver';
                                    @endphp
                                    {{ $index + 1 }}. {{ $stepName }} <span class="{{ $stepType === 'releaser' ? 'text-purple-600 font-bold' : 'text-blue-600' }}" title="{{ $stepType === 'releaser' ? 'Releaser Phase' : 'Approval Phase' }}">{{ $stepType === 'releaser' ? '(R)' : '(A)' }}</span>@if(!$loop->last), @endif
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="w-1/12 px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $workflow->requests_count }}</div>
                    </td>
                    <td class="w-1/12 px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $workflow->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $workflow->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="w-1/12 px-6 py-4 text-sm text-gray-500">
                        {{ $workflow->created_at->format('d M Y') }}
                    </td>
                    <td class="w-1/12 px-6 py-4 text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('approval-workflows.show', $workflow) }}" 
                               class="text-blue-600 hover:text-blue-900 transition-colors duration-150">Lihat</a>
                            <a href="{{ route('approval-workflows.edit', $workflow) }}" 
                               class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150">Edit</a>
                            <form action="{{ route('approval-workflows.toggle-status', $workflow) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900 transition-colors duration-150">
                                    {{ $workflow->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form action="{{ route('approval-workflows.destroy', $workflow) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-150" 
                                        onclick="return confirm('Yakin ingin menghapus workflow ini?')">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-responsive-table>
@endsection

