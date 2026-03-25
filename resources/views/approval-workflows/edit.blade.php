@extends('layouts.app')

@section('title', 'Edit Approval Workflow')

@section('content')
    <div class="w-full">
        <div class="bg-white overflow-hidden shadow-sm">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Edit Approval Workflow</h2>
                    <a href="{{ route('approval-workflows.index') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Kembali
                    </a>
                </div>
            </div>

            <div class="p-6">
                @include('approval-workflows._form', [
                    'action' => route('approval-workflows.update', $approvalWorkflow),
                    'approvalWorkflow' => $approvalWorkflow
                ])
            </div>
        </div>
    </div>
@endsection
