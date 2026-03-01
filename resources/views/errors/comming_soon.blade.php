@extends('layouts.lite')

@section('page_content')

	<div class="row justify-content-center">

	    <div class="col-10">

	        <div class="text-center">

	            <img src="/source/base/images/animat-rocket-color.gif" alt="" height="160">

	            <h3 class="mt-4">Stay tunned, we're launching very soon</h3>
	            <p class="text-muted">We're making the system more awesome.</p>

	            <div class="row mt-5 justify-content-center">
	                <div class="col-md-8">
	                    <div data-countdown="2025/12/17" class="counter-number"></div>
	                </div> <!-- end col-->
	            </div> <!-- end row-->
	        </div> <!-- end /.text-center-->

	    </div> <!-- end col -->

	</div>
	<!-- end row -->

@endsection

@section('page_java_script')

	<!-- Plugins js-->
	<script src="/source/base/libs/jquery-countdown/jquery.countdown.min.js"></script>

	<!-- Countdown js -->
    <script src="/source/base/js/pages/coming-soon.init.js"></script>

@endsection
