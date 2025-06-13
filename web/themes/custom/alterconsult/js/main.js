(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.alterconsultTheme = {
    attach: function (context, settings) {
      // Initialize benefits carousel
      if (context === document) {
        initBenefitsCarousel();
      }

      // Smooth scroll for anchor links
      $('a[href*="#"]:not([href="#"])').once('smooth-scroll').on('click', function(e) {
        e.preventDefault();
        var target = $(this.hash);
        if (target.length) {
          $('html, body').animate({
            scrollTop: target.offset().top - 100
          }, 1000);
        }
      });

      // Mobile menu toggle
      $('.mobile-menu-toggle').once('mobile-menu').on('click', function() {
        $('.main-navigation').toggleClass('active');
      });
    }
  };

  function initBenefitsCarousel() {
    // Initialize the benefits carousel using Slick slider
    if ($.fn.slick) {
      $('.benefits-carousel').slick({
        dots: true,
        infinite: true,
        speed: 300,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
          {
            breakpoint: 1024,
            settings: {
              slidesToShow: 2,
              slidesToScroll: 1
            }
          },
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }
        ]
      });
    }
  }

})(jQuery, Drupal);