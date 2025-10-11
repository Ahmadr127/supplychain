@extends('layouts.app')

@section('title', 'Edit Approval Request')

@section('content')
    <div class="bg-white overflow-hidden w-full shadow-none rounded-none">
        <div class="p-2">
            <form id="approval-form" action="{{ route('approval-requests.update', $approvalRequest) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('approval-requests._form', [
                    'mode' => 'edit',
                    'defaultWorkflow' => $defaultWorkflow,
                    'approvalRequest' => $approvalRequest,
                    'submissionTypes' => $submissionTypes,
                    'itemTypes' => $itemTypes,
                ])
            </form>
        </div>
    </div>
</div>
@include('components.modals.form-master-items')
@endsection
