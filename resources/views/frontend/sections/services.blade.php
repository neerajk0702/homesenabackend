 <!-- Services Section — Arc Focus Carousel -->
 <section id="services" class="services-section">

     <div class="container-fluid">

         <h3 class="arc-section-heading">Our Services</h3>
         <p class="arc-section-subheading">
             We provide on-demand, verified, and trained home help services at your doorstep.
         </p>

         <!-- Arc Carousel -->
         <div class="arc-carousel" id="arcCarousel">

             <!-- Carousel viewport -->
             <div class="arc-carousel__viewport">

                 <div class="arc-carousel__item" data-index="0">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Bathroom Cleaning.png') }}" alt="Bathroom Cleaning">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="1">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Lanudary.png') }}" alt="Laundry">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="2">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Dish Washing.png') }}" alt="Dish Washing">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="3">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Outdoor Cleaning.png') }}" alt="Outdoor Cleaning">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="4">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/kitchen cleaning.png') }}" alt="Kitchen Cleaning">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="5">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Window Cleaning.png') }}" alt="Window Cleaning">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="6">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Home Cleaning.png') }}" alt="Home Cleaning">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="7">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Dusting and wiping.png') }}" alt="Dusting and wiping">
                     </div>
                 </div>

                 <div class="arc-carousel__item" data-index="8">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Kitchen Prep.png') }}" alt="Kitchen Prep">
                     </div>
                 </div>
                 <div class="arc-carousel__item" data-index="9">
                     <div class="arc-card">
                         <img src="{{ asset('landing/img/service/Ironing.png') }}" alt="Ironing">
                     </div>
                 </div>

             </div>

             <!-- Content area below the arc -->
             <div class="arc-carousel__info">
                 <h4 class="arc-carousel__title" id="arcActiveTitle">Bathroom Cleaning</h4>
                 <p class="arc-carousel__subtitle" id="arcActiveSubtitle">Sparkling clean bathrooms with deep sanitization</p>
             </div>

             <!-- Navigation arrows -->
             <button class="arc-carousel__arrow arc-carousel__arrow--left" id="arcPrev" aria-label="Previous service">
                 <i class="fa fa-chevron-left"></i>
             </button>
             <button class="arc-carousel__arrow arc-carousel__arrow--right" id="arcNext" aria-label="Next service">
                 <i class="fa fa-chevron-right"></i>
             </button>

             <!-- Dot indicators -->
             <div class="arc-carousel__dots" id="arcDots"></div>

         </div>

     </div>
 </section>
 <!-- Services Section end-->
