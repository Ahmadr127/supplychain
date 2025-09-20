@extends('layouts.app')

@section('title', 'Detail Approval Workflow')

@section('content')
<div class="w-full mx-auto max-w-6xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $approvalWorkflow->name }}</h2>
                    <p class="text-gray-600">{{ $approvalWorkflow->type }} - {{ $approvalWorkflow->description }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-workflows.edit', $approvalWorkflow) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Edit
                    </a>
                    <a href="{{ route('approval-workflows.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Workflow Info -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Workflow</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nama Workflow</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approvalWorkflow->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipe</label>
                                <p class="mt-1">
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                        {{ $approvalWorkflow->type }}
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $approvalWorkflow->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $approvalWorkflow->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Steps</label>
                                <p class="mt-1 text-sm text-gray-900">{{ count($approvalWorkflow->workflow_steps) }} steps</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Requests</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approvalWorkflow->requests->count() }} requests</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dibuat</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approvalWorkflow->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($approvalWorkflow->description)
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $approvalWorkflow->description }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Workflow Steps -->
                    <div class="bg-white border border-gray-200 rounded-lg mt-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Workflow Steps</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @foreach($approvalWorkflow->workflow_steps as $index => $step)
                                <div class="flex items-center p-4 border border-gray-200 rounded-lg bg-gray-50">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                            <span class="text-white font-medium text-sm">{{ $index + 1 }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $step['name'] }}</p>
                                                <p class="text-xs text-gray-500">
                                                    @if($step['approver_type'] == 'user' && isset($step['approver_id']))
                                                        @php
                                                            $user = \App\Models\User::find($step['approver_id']);
                                                        @endphp
                                                        User: {{ $user ? $user->name : 'User not found' }}
                                                    @elseif($step['approver_type'] == 'role' && isset($step['approver_role_id']))
                                                        @php
                                                            $role = \App\Models\Role::find($step['approver_role_id']);
                                                        @endphp
                                                        Role: {{ $role ? $role->display_name : 'Role not found' }}
                                                    @elseif($step['approver_type'] == 'department_manager' && isset($step['approver_department_id']))
                                                        @php
                                                            $dept = \App\Models\Department::find($step['approver_department_id']);
                                                        @endphp
                                                        Department Manager: {{ $dept ? $dept->name : 'Department not found' }}
                                                    @elseif($step['approver_type'] == 'department_level' && isset($step['approver_level']))
                                                        Department Level: {{ $step['approver_level'] }}
                                                    @else
                                                        Approver: Not configured
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Step {{ $index + 1 }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Workflow Stats -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistik</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Requests</span>
                                <span class="text-sm font-medium text-gray-900">{{ $approvalWorkflow->requests->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Pending</span>
                                <span class="text-sm font-medium text-gray-900">{{ $approvalWorkflow->requests->where('status', 'pending')->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Approved</span>
                                <span class="text-sm font-medium text-gray-900">{{ $approvalWorkflow->requests->where('status', 'approved')->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Rejected</span>
                                <span class="text-sm font-medium text-gray-900">{{ $approvalWorkflow->requests->where('status', 'rejected')->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Requests -->
                    @if($approvalWorkflow->requests->count() > 0)
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Requests</h3>
                        <div class="space-y-3">
                            @foreach($approvalWorkflow->requests->take(5) as $request)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $request->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $request->requester->name }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $request->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($request->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                           ($request->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @if($approvalWorkflow->requests->count() > 5)
                        <div class="mt-4 text-center">
                            <a href="{{ route('approval-requests.index', ['workflow_id' => $approvalWorkflow->id]) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                Lihat Semua Requests
                            </a>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
