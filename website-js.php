

    <script src="slider/owl.carousel.js" type="text/javascript"></script>
    <script>

          $(function() {

            $(window).on("scroll", function() {

                if ($(window).scrollTop() > 94) {

                    $(".topheader_outer").addClass("fixedmenu");
                    $(".banner-section").css("margin-top", "130px");

                } else {

                    //remove the background property so it comes transparent again (defined in your css)

                    $(".topheader_outer").removeClass("fixedmenu");
                    $(".banner-section").css("margin-top", "0px");

                }

            });

            });    

$(document).ready(function() {
  

	  
      var owl_at = $('#banner_slider')
  	    owl_at.owlCarousel({
        navigation :true,
        autoHeight : true,
        autoPlay : true,
		pagination: false,
        items : 1,
        itemsDesktop : [1380, 1],
        itemsDesktopSmall : [1050, 1],
        itemsTablet : [768, 1],
        itemsTabletSmall : false,
        itemsMobile : [480, 1]
      });
      owl_at.trigger('stop.owl.autoplay');

      
	  
      var owl_at1 = $('#serviceslider')
  	    owl_at1.owlCarousel({
        navigation :true,
        autoHeight : true,
        autoPlay : true,
		pagination: false,
        items : 4,
        itemsDesktop : [1380, 4],
        itemsDesktopSmall : [1050, 3],
        itemsTablet : [768, 2],
        itemsTabletSmall : false,
        itemsMobile : [480, 1]
      });
      owl_at1.trigger('stop.owl.autoplay');
	  
      var owl_at2 = $('#testsilder')
  	    owl_at2.owlCarousel({
        navigation :false,
        autoHeight : true,
        autoPlay : true,
		pagination: true,
        items : 1,
        itemsDesktop : [1380, 1],
        itemsDesktopSmall : [1050, 1],
        itemsTablet : [768, 1],
        itemsTabletSmall : false,
        itemsMobile : [480, 1]
      });
      owl_at2.trigger('stop.owl.autoplay');

      

      });

      

$(document).ready(function () {
    let counterStarted = false;

    function isScrolledIntoView(elem) {
        const docViewTop = $(window).scrollTop();
        const docViewBottom = docViewTop + $(window).height();
        const elemTop = $(elem).offset().top;
        const elemBottom = elemTop + $(elem).height();
        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    }

    function startCounter() {
        $('.counter').each(function () {
            $(this).prop('Counter', 0).animate({
                Counter: $(this).text()
            }, {
                duration: 4000,
                easing: 'swing',
                step: function (now) {
                    $(this).text(Math.ceil(now));
                }
            });
        });
    }

    $(window).on('scroll', function () {
        if (!counterStarted && isScrolledIntoView($('.counter').first())) {
            counterStarted = true;
            startCounter();
        }
    });
});




    </script>

    



        
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const steps = document.querySelectorAll('.hhowports_in');
    let current = 0;

    function activateNext() {
      // Remove 'active' class from all
      steps.forEach(step => step.classList.remove('active'));

      // Add 'active' to the current one
      steps[current].classList.add('active');

      // Move to next, loop back if needed
      current = (current + 1) % steps.length;
    }

    // Start the loop
    activateNext(); // initial activation
    setInterval(activateNext, 2000); // every 2 seconds
  });
</script>