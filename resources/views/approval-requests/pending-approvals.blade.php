@extends('layouts.app')

@section('title', 'Pending Approvals')

@section('content')
<div class="w-full mx-auto" x-data="{
    ...tableFilter({
        search: '{{ request('search') }}'
    })
}">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Pending Approvals</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.my-requests') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        My Requests
                    </a>
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        All Requests
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Filter -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <form method="GET" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari nomor request, judul, atau requester..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Search
                </button>
            </form>
        </div>

        @if($pendingApprovals->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Request
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Requester
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Workflow
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Current Step
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pendingApprovals as $step)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-2">
                                        {{ $step->request->request_number }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900">{{ $step->request->title }}</div>
                                @if($step->request->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($step->request->description, 50) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-600 text-xs font-medium">
                                            {{ substr($step->request->requester->name, 0, 2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $step->request->requester->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $step->request->requester->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $step->request->workflow->name }}</div>
                            <div class="text-xs text-gray-500">{{ $step->request->workflow->type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $step->step_name }}</div>
                            <div class="text-xs text-gray-500">Step {{ $step->step_number }} of {{ $step->request->total_steps }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $step->request->created_at->format('d M Y') }}</div>
                            <div class="text-xs">{{ $step->request->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('approval-requests.show', $step->request) }}" 
                                   class="text-blue-600 hover:text-blue-900">Review</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($pendingApprovals->hasPages())
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $pendingApprovals->links() }}
        </div>
        @endif
        @else
        <div class="p-12 text-center">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <i class="fas fa-check-circle text-4xl"></i>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada pending approvals</h3>
            <p class="mt-1 text-sm text-gray-500">Semua request yang memerlukan approval Anda sudah diproses.</p>
            <div class="mt-6">
                <a href="{{ route('approval-requests.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Lihat Semua Requests
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
