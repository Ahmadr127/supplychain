@extends('layouts.app')

@section('title', 'Edit Approval Request')

@section('content')
<div class="w-full max-w-full overflow-hidden px-0">
    <div class="bg-white w-full max-w-full shadow-none rounded-none">
        <div class="p-2 max-w-full overflow-hidden">
            <form id="approval-form" action="{{ route('approval-requests.update', $approvalRequest) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('approval-requests._form', [
                    'mode' => 'edit',
                    'defaultWorkflow' => $defaultWorkflow,
                    'approvalRequest' => $approvalRequest,
                    'submissionTypes' => $submissionTypes,
                    'itemTypes' => $itemTypes,
                    'itemCategories' => $itemCategories,
                    'itemExtras' => $itemExtras ?? collect(),
                    'itemFiles' => $itemFiles ?? collect(),
                ])
            </form>
        </div>
    </div>
</div>
@include('components.modals.form-master-items')
@include('approval-requests._form-extra')
@endsection
