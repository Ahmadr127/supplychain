@extends('layouts.app')

@section('title', 'My Requests')

@section('content')
<div class="w-full mx-auto" x-data="{
    ...tableFilter({
        search: '{{ request('search') }}',
        status: '{{ request('status') }}'
    })
}">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">My Requests</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.pending-approvals') }}" 
                       class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                        Pending Approvals
                    </a>
                    <a href="{{ route('approval-requests.create') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Buat Request
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari nomor request atau judul..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        @if($requests->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Request
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Workflow
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progress
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
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
                    @foreach($requests as $request)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-2">
                                        {{ $request->request_number }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900">{{ $request->title }}</div>
                                @if($request->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($request->description, 50) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $request->workflow->name }}</div>
                            <div class="text-xs text-gray-500">{{ $request->workflow->type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Step {{ $request->current_step }}</span>
                                        <span>{{ $request->total_steps }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" 
                                             style="width: {{ ($request->current_step / $request->total_steps) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $request->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($request->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                   ($request->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $request->created_at->format('d M Y') }}</div>
                            <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('approval-requests.show', $request) }}" 
                                   class="text-blue-600 hover:text-blue-900">Lihat</a>
                                @if($request->status == 'pending')
                                    <a href="{{ route('approval-requests.edit', $request) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $requests->links() }}
        </div>
        @endif
        @else
        <div class="p-12 text-center">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <i class="fas fa-file-alt text-4xl"></i>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada request</h3>
            <p class="mt-1 text-sm text-gray-500">Anda belum membuat approval request apapun.</p>
            <div class="mt-6">
                <a href="{{ route('approval-requests.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>
                    Buat Request Pertama
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
