jQuery(document).ready(function() {
    if (navigator.appVersion.indexOf("MSIE 10") != -1) {
        jQuery("html").addClass("ie10");
    }
    function introHeight() {
        var wh = jQuery(window).height();
        jQuery('#intro').css({'height':wh+'px'});
        //jQuery('#intro-bg').css({'height':wh+'px'});
    };
    jQuery(window).resize(function() {
      introHeight();
    });
    jQuery(window).on("load resize scroll",function(e){
        var screenTop = jQuery(window).scrollTop();
        var screenWidth = jQuery(window).width();
        if((screenTop > 0) && (screenTop < 1600)) {
            introScale = (100+(screenTop/40))/100;
            h1Top = 0 + (screenTop/2);
        } else if(screenTop > 1600) {
            //do nothing
        }
        else {
            introScale = '1.0'
            h1Top = 0;
        };
        jQuery('#intro-bg').css({
            '-webkit-transform': 'scale('+introScale+')',
            '-moz-transform': 'scale('+introScale+')',
            '-o-transform': 'scale('+introScale+')',
            '-ms-transform': 'scale('+introScale+')',
            'transform': 'scale('+introScale+')'
        });
    

    });
    introHeight();

    
    jQuery('#logo').click(function(e) {
        e.preventDefault();
        jQuery('.intro').click();
    });
    jQuery('.about-scroll').click(function(e) {
        e.preventDefault();
        jQuery('.about').click();
    });
    jQuery('.booking-scroll').click(function(e) {
        e.preventDefault();
        jQuery('.booking').click();
    });
    jQuery('#menu').onePageNav({
        scrollOffset: 62
    });
    jQuery('#intro').waypoint(function() {
        jQuery(this).addClass('active');
    }, {
        offset: '100%'
    });
    jQuery('#media').waypoint(function() {
        jQuery(this).addClass('active');
    }, {
        offset: '70%'
    });
    jQuery('.case').waypoint(function() {
        jQuery(this).addClass('active');
    }, {
        offset: '74%'
    });
    jQuery('#booking .row').waypoint(function() {
        jQuery(this).addClass('active');
    }, {
        offset: '95%'
    });
    jQuery('.footer-container').waypoint(function() {
        jQuery(this).addClass('active');
    }, {
        offset: '99%'
    });
    jQuery('#contact').waypoint(function() {
        jQuery(this).addClass('active');
    }, {
        offset: '80%'
    });
    jQuery('#contact').waypoint(function() {
        jQuery('#start-project').stop().animate({
            marginRight: '-1000px'
        }, 200);
    }, {
        offset: '60%'
    });
    jQuery('#contact').waypoint(function() {
        jQuery('#start-project').stop().animate({
            marginRight: '0'
        }, 200);
    }, {
        offset: '61%'
    });
});

jQuery(function() {
    if (window.PIE) {
        jQuery('h3 i').each(function() {
            PIE.attach(this);
        });
        jQuery('.social i').each(function() {
            PIE.attach(this);
        });
    }
});


jQuery(document).ready(function() {
    // Check to see if the browser already supports placeholder text (introduced in HTML5). If it does,
    // then we don't need to do anything.
    var i = document.createElement('input');
    if ('placeholder' in i) {
        return;
    }
    
    // Released under MIT license: http://www.opensource.org/licenses/mit-license.php
 
    jQuery('[placeholder]').focus(function() {
      var input = jQuery(this);
      if (input.val() == input.attr('placeholder')) {
        input.val('');
        input.removeClass('placeholder');
      }
    }).blur(function() {
      var input = jQuery(this);
      if (input.val() == '' || input.val() == input.attr('placeholder')) {
        input.addClass('placeholder');
        input.val(input.attr('placeholder'));
      }
    }).blur().parents('form').submit(function() {
      jQuery(this).find('[placeholder]').each(function() {
        var input = jQuery(this);
        if (input.val() == input.attr('placeholder')) {
          input.val('');
        }
      })
    });
});


