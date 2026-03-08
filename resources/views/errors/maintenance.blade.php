@extends('layouts.lite')

@section('page_content')

<div class="text-center">
    <img src="/source/base/images/animat-diamond-color.gif" alt="" height="160">
    <h3 class="mt-1"> {{ __('errors.errors.503.page_title') }} </h3>
    <p class="text-muted"> {{ __('errors.errors.503.text') }} </p>

    <div class="row mt-5">
        <div class="col-md-4">
            <div class="text-center mt-3 px-1">
                <div class="avatar-md rounded-circle bg-soft-primary mx-auto">
                    <i class="fe-target font-22 avatar-title text-primary"></i>
                </div>
                <h5 class="font-16 mt-3"> {{ __('errors.errors.503.descriptions.left.title') }} </h5>
                <p class="text-muted"> {{ __('errors.errors.503.descriptions.left.text') }} </p>
            </div>
        </div> <!-- end col-->
        <div class="col-md-4">
            <div class="text-center mt-3 px-1">
                <div class="avatar-md rounded-circle bg-soft-primary mx-auto">
                    <i class="fe-clock font-22 avatar-title text-primary"></i>
                </div>
                <h5 class="font-16 mt-3"> {{ __('errors.errors.503.descriptions.center.title') }} </h5>
                <p class="text-muted"> {{ __('errors.errors.503.descriptions.center.text') }} </p>
            </div>
        </div> <!-- end col-->
        <div class="col-md-4">
            <div class="text-center mt-3 px-1">
                <div class="avatar-md rounded-circle bg-soft-primary mx-auto">
                    <i class="fe-help-circle font-22 avatar-title text-primary"></i>
                </div>
                <h5 class="font-16 mt-3"> {{ __('errors.errors.503.descriptions.right.title') }} </h5>
                <p class="text-muted"> {!! __('errors.errors.503.descriptions.right.text') !!} </p>
            </div>
        </div> <!-- end col-->
    </div> <!-- end row-->
</div> <!-- end /.text-center-->

@endsection
