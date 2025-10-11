@extends('layouts.app')

@section('title', 'Buat Approval Request')

@section('content')
<div class="w-full px-0">
    <div class="bg-white overflow-visible w-full shadow-none rounded-none">
        <div class="p-2">
            <form id="approval-form" action="{{ route('approval-requests.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('approval-requests._form', [
                    'mode' => 'create',
                    'defaultWorkflow' => $defaultWorkflow,
                    'previewRequestNumber' => $previewRequestNumber ?? '',
                    'submissionTypes' => $submissionTypes,
                    'itemTypes' => $itemTypes,
                ])
            </form>
        </div>
    </div>
</div>

<!-- Include Modal Form for Adding Items -->
@include('components.modals.form-master-items')
@endsection
