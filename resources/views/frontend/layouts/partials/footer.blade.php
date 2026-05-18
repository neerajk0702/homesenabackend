<section id="footer" class="footer-section text-white">
    <div class="container">

        <div class="footer-content">

            <!-- Logo -->
            <div class="footer-logo text-center text-md-start">
                <img src="{{ asset('landing/img/footer_logo.png') }}" alt="Homesena" class="footer-logo-img">
                <p>Your trusted partner for professional home services.</p>
            </div>

            <!-- Footer Row -->
            <div class="footer-row">

                <!-- LEFT LINKS -->
                <div class="footer-left">
                    @foreach ($cms_pages as $page)
                        <a href="{{ url('page/' . $page->slug) }}">
                            {{ $page->title }}
                        </a>
                    @endforeach
                    <a href="{{ route('support') }}">Contact Us</a> 
                  <a href="{{ route('delete.account') }}">Delete Account</a> 
                </div>

                <!-- CENTER (EMPTY / OPTIONAL) -->
                <div class="footer-center"></div>

            </div>

        </div>

        <!-- COPYRIGHT + SOCIAL -->
        <div class="copyright">

            <p class="copyright-left">
                Made with <i class="fa fa-heart text-danger"></i> by Homesena
            </p>

            <div class="footer-center">
                <a href="https://www.facebook.com/people/Thehomesena/61576438709604/"><i
                        class="fa fa-facebook text-white"></i></a>
                <a href="https://www.instagram.com/thehomesena?igsh=d3l0a2h3bG92dGJr"><i
                        class="fa fa-instagram text-white"></i></a>
            </div>

            <p class="copyright-right">
                © 2026 Homesena. All Rights Reserved.
            </p>

        </div>

    </div>
</section>