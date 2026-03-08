<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-menu-color="dark">

@include('includes/head')

<body>

    <div class="account-pages mt-5 mb-5">
        <div class="container">

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            {{-- Content --}}
            @yield('page_content')

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

            {{-- Footer (lite) --}}
            @include('includes/footer/lite')

        </div>
        <!-- end container -->

    </div>
    <!-- end page -->

    <!-- Vendor js -->
    <script src="/source/base/js/vendor.min.js"></script>

    {{-- Java Script --}}
    @yield('page_java_script')

    <!-- App js -->
    <script src="/source/base/js/app.min.js"></script>

    {{-- More Java Script --}}
    @yield('page_more_java_script')
  
</body>
</html>