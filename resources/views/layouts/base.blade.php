<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-menu-color="dark">
    
    @include('includes.head')

    <body>

        <!-- Begin page -->
        <div id="wrapper">

            <!-- Pre-loader -->
            <div id="preloader">
                <div id="status">
                    <div class="spinner">Loading...</div>
                </div>
                <div class="info"></div>
            </div>
            <!-- End Preloader-->

            {{-- Header --}}
            @include('includes.topbar')
            
            {{-- Left menu --}}
            @include('includes.left_menu')
      
            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">

                        {{-- Content --}}
                        @yield('page_content')

                    </div> <!-- container -->

                </div> <!-- content -->

                {{-- Footer (base) --}}
                @include('includes.footer.base')

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->

        {{-- System settings menu --}}
        @include('includes.system_settings')

        <!-- Vendor js -->
        <script src="/source/base/js/vendor.min.js"></script>

        {{-- Java Script --}}
        @yield('page_java_script')

        <!-- App js -->
        <script src="/source/base/js/app.min.js"></script>

        <!-- Global scripts -->
        <script src="/source/base/js/default.js?<?=time()?>"></script>

        {{-- More Java Script --}}
        @yield('page_more_java_script')

        {{-- Здесь выведется все что добавлялось через: @push('name') + @endpush 
        @stack('scripts')--}}
        
    </body>
</html>