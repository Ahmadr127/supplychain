@extends('layouts.app')

@section('title', 'Buat Approval Request')

@section('content')
<div class="w-full max-w-full overflow-hidden px-0">
    <div class="bg-white w-full max-w-full shadow-none rounded-none">
        <div class="p-2 max-w-full overflow-hidden">
            <form id="approval-form" action="{{ route('approval-requests.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('approval-requests._form', [
                    'mode' => 'create',
                    'defaultWorkflow' => $defaultWorkflow,
                    'previewRequestNumber' => $previewRequestNumber ?? '',
                    'submissionTypes' => $submissionTypes,
                    'itemTypes' => $itemTypes,
                    'itemCategories' => $itemCategories,
                ])
            </form>
        </div>
    </div>
</div>

<!-- Include Modal Form for Adding Items -->
@include('components.modals.form-master-items')
@endsection
