<!-- Footer Start -->
<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                {{-- Footer (text) --}}
                @include('includes/footer/text') 
            </div>
            <div class="col-md-6">
                <div class="text-md-end footer-links d-none d-sm-block">
                    <a href="javascript:void(0);"> {{ __('footer.navigation.about_us') }} </a>
                    <a href="javascript:void(0);"> {{ __('footer.navigation.help') }} </a>
                    <a href="javascript:void(0);"> {{ __('footer.navigation.contact_us') }} </a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- end Footer -->