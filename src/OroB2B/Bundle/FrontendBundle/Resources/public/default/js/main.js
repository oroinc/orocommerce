(function($, _) {
  'use strict'

  var oroCommerce = OroCommerce();

  function OroCommerce() {
    var app = app || {},
        catalogViews = { 
          gallery: 'gallery-view',
          list: 'list-view',
          noImage: 'no-image-view'
        };

    app.init = function() {
      sidebarToggleBinding(); //Sidebar toggling binding function 
      messagesAccordionBinding(); //Messages accordion binding function  
      accountAccordionBinding(); //Accordion accordion binding function 
      tabToggleBinding(); //Tabs toggling binding function
      ratyInit(); //Initing function of raty plugin
      chosenInit(); // Initing function of chosen (custom selectboxes) plugin
      stickyNavigationInit(); // Init mobile sticky navigation
      ieDetectInit(); //InterNet Explorer detecting function
      mobileNavigationButtonsBinding(); //Mobile navigation button binding
      clickElseWhereBinding(); //Click outside the container binding
      toggleNestedListBinding(); //Toggling of nested lists binding
      heroSliderInit(); //Initing of hero slider (fading slick slider)
      productsSliderInit(); //Initing of products slider (slick slider)
      moreInfoExpandBinding(); //More info button binding
      pinClickBinding(); //Pin click binding
      customCheckboxBinding();
      customRadioBinding();
      filterWidgetToggleBinding();
      datepickerInit();
      topbarButtonsBinding();
      customInputFileInit();

      countInit().init({
        plus: '[data-count-plus]',
        minus: '[data-count-minus]',
        inputNum: '[data-count-input]'
      });

      //On page load init list view catalog
      catalogViewTemplatingInit(catalogViews.list);
    };

    function sidebarToggleBinding() {
      var btn = '[data-sidebar-trigger]';

      $(document).on('click', btn, function(event) {
        $(this)
          .toggleClass('active')
          .closest('[data-collapse-sidebar]').toggleClass('sidebar_collapsed')
          .end()
          .closest('.main').find('[data-collapse-content]').toggleClass('content_sidebar-collapsed');   
 
          //Reinitializing of hero carousel
          if ($('[data-hero-slider]').length) {
            $('[data-hero-slider]').slick('unslick');
            heroSliderInit();
          }

          //Reinitializing of products carousel
          if ($('[data-products-slider]').length) {
            $('[data-products-slider]').slick('unslick');
            productsSliderInit();
          }
      });
    }

    function messagesAccordionBinding() {
      var trigger = '[data-messages-trigger]',
          $messages = $('[data-messages-widget]'),
          $container = $('[data-messages-container]');

      $(document).on('click', trigger, function(event) {
        $messages.toggleClass('messages-widget__list-expanded');  
        $container.toggleClass('hidden');  

        event.preventDefault();
      });
    }

    function accountAccordionBinding() {
      var trigger = '[data-account-trigger]',
          $messages = $('[data-account-widget]'),
          $container = $('[data-account-container]');

      $(document).on('click', trigger, function(event) {
        $messages.toggleClass('account-widget__list-expanded');  
        $container.toggleClass('hidden');  

        event.preventDefault();
      });
    }

    function tabToggleBinding() {
      var tabTrigger = '[data-tab-trigger]';

      $(document).on('click', tabTrigger, function(event) {
        $('[data-tab]').removeClass('active');
        $(this).parent().addClass('active');   

        //Reinitializing of products carousel
        if ($('[data-products-slider]').length) {
          $('[data-products-slider]').slick('unslick');
          productsSliderInit();
        }

        event.preventDefault();
      });
    }

    function ratyInit() {
      var $rating = $('[data-rating]'),
          $rated = $('[data-rated]');

      if ($rating.length) {
        $rating.each(function() {
          $(this).raty({
            number: 4,
            score: $(this).data('rating') !== '' ? $(this).data('rating') : 3,
            starType: 'i'
          });
        });
      }

      if ($rated.length) {
        $rated.raty({
          readOnly: true,
          number: 4,
          score: 3, 
          starType: 'i' 
        });
      }
    }

    function chosenInit() {
      var $selectbox = $('[data-chosen-selectbox]');
         
      if ($selectbox.length) {
        $selectbox.chosen({
          disable_search_threshold: 10, 
          width: "100%"
        });    
      }
    }

    function stickyNavigationInit() {
      var $sticky = $('[data-sticky-navigation]'),
          $main = $('.main'),
          $win = $(window);

      $win.on('scroll', function() {
        if (this.hasOwnProperty('scrollY')) {
          if (this.scrollY >= 53) {
            $sticky.addClass('sticky');  
            $main.addClass('sticky-nav');
          } else {
            $sticky.removeClass('sticky');
            $main.removeClass('sticky-nav');    
          }
        } else {
          if (this.pageYOffset >= 53) {
            $sticky.addClass('sticky'); 
            $sticky.addClass('sticky');     
          } else {
            $sticky.removeClass('sticky');   
            $main.removeClass('sticky-nav');   
          }
        }
      });     
    }

    function ieDetectInit() {
      if (window.navigator.appName.indexOf("Internet Explorer") !== -1) {
        $('html').addClass('ie');
      }

      if (window.navigator.msPointerEnabled) {
        $('html').addClass('ie');
      }
    }

    function mobileNavigationButtonsBinding() {
      var btn = '[data-trigger-mobile-navigation]',
          container = '[data-mobile-navigation-dropdown]';

      $(document).on('touchstart', btn, function(event) {
        $(btn).removeClass('active');
        $(container).removeClass('active');

        mobileNavigitionDropdownToWindowSize();
        $('body').addClass('hidden-scrollbar').css('overflow', 'hidden');
        $(this).addClass('active');
        $(this).find(container).addClass('active');      

        event.stopPropagation(); 
        event.cancelBubble = true;
      });
    }

    function clickElseWhereBinding() {
      var $btn = $('[data-trigger-mobile-navigation]'), 
          $container = $('[data-mobile-navigation-dropdown]');

      $(document).on('touchend', function(event) {
        if (!$container.is(event.target) && $container.has(event.target).length === 0 && !$btn.is(event.target) && !$btn.has(event.target).length) {
          $('body.hidden-scrollbar').removeClass('hidden-scrollbar').css('overflow', 'auto');
          $btn.removeClass('active');
          $container.removeClass('active');
          $container.css('max-height', 'auto');
        }    
      });        
    }

    function mobileNavigitionDropdownToWindowSize() {
      var $win = $(window),
          $nav = $('[data-sticky-navigation]'),
          $middleBar = $('.middlebar'),
          middleBarHeight = $middleBar.outerHeight(),
          winHeight = $win.outerHeight(),
          navHeight = $nav.outerHeight(),
          $container = $('[data-mobile-navigation-dropdown]');

      if ($nav.hasClass('sticky')) {
        $container.css('max-height', winHeight - navHeight); 
      } else {
        $container.css('max-height', winHeight - navHeight - middleBarHeight);
      }       
    }

    function toggleNestedListBinding() {
      var trigger = '[data-trigger-nested-list]',
          $list = $('[data-nested-list]');

      $(document).on('touchstart', trigger, function(event) {
        $(trigger).toggleClass('expanded');
        $list.toggleClass('expanded');

        event.preventDefault();
      });    
    }

    function heroSliderInit() {
      var $hero = $('[data-hero-slider');

      if ($hero.length) {
        $hero.slick({
          autoplay: true,
          dots: true,
          infinite: true,
          speed: 500,
          fade: true,
          cssEase: 'linear',
          prevArrow: false,
          nextArrow: false
        });
      }
    }

    function productsSliderInit() {
      var $products = $('[data-products-slider]'),
          slidesToShowMd = $products.data('slides-to-show-md'),
          slidesToShowSm = $products.data('slides-to-show-sm');

      if ($products.length) {
        $products.slick({
          dots: false,
          infinite: false,
          speed: 300,
          slidesToShow: slidesToShowMd,
          slidesToScroll: slidesToShowMd,
          responsive: [
            {
              breakpoint: 1200,
              settings: {
                slidesToShow: slidesToShowSm,
                slidesToScroll: slidesToShowSm,
              }
            },
            {
              breakpoint: 992,
              settings: 'unslick'
            }
          ]
        });
      }
    }

    function moreInfoExpandBinding() {
      var btn = '[data-more-info-expand]';

      $(document).on('click', btn, function(event) {
        event.preventDefault();
        $(this).parent().find('i').toggleClass('expanded');
        $(this).parent().find('[data-more-info]').slideToggle();
      });    
    }

    function countInit() {
      var Count = Count || {};
      
      Count.init = function(config) {
        this.plus = config.plus;
        this.minus = config.minus;
        this.inputNum = config.inputNum;

        this.plusEvent.click.call(this);
        this.minusEvent.click.call(this);
      };
        
      Count.plusEvent = {
        click: function() {
          var self = this;

          $(document).on('click', self.plus, function(event) {
            event.preventDefault();
            var $input = $(this).parent().find(self.inputNum),
                value = $input.val(),
                current;

            if (typeof value === 'string' && value === '') {
              value = 0;
              current = parseInt(value); 
            } else {
              current = parseInt(value);      
            }
            
            current++;
            $input.val(current);          
          });
        }
      };
        
      Count.minusEvent = {
        click: function() {
          var self = this;

          $(document).on('click', self.minus, function(event) {
            event.preventDefault();
            var $input = $(this).parent().find(self.inputNum),
                current = $input.val();

            if (current > 1) {
              current--;
            } else {
              return false;
            }

            $input.val(current);
          });
        }
      };

      return Count;
    }

    function catalogViewTemplatingInit(view) {
      var $wrapper = $('[data-products-list-wrapper]'),
          $viewTemplate = $('[data-catalog-view-template]'),
          viewTrigger = '[data-catalog-view-trigger]',
          productsList = _.template($viewTemplate.html());    

      //Render template on init
      $wrapper.html(productsList({
        view: {
          class: view
        }
      })); 

      $(document).on('click', viewTrigger, function(event) {
        var view = $(this).data('catalog-view-trigger'),
            $btns = $('[data-catalog-view-trigger]');

        $btns.removeClass('current');

        $btns.each(function(index) {
          if ($(this).data('catalog-view-trigger') === view) {
            $(this).addClass('current');
          }
        });

        //Render template, when change the view
        $wrapper.html(productsList({
          view: {
            class: view
          }
        }));

        event.preventDefault();
      });
    }

    function pinClickBinding() {
      var pinBtn = '[data-pin-trigger]',
          pinContent = '[data-pin-content]';

      $(document).on('click', pinBtn,function(event) {
        $(this).closest('.pin-widget').find(pinContent).toggle();
        event.preventDefault();
      });    

      $(document).on('click', function(event) {
        if (!$(pinContent).is(event.target) && $(pinContent).has(event.target).length === 0 && !$(pinBtn).is(event.target) && !$(pinBtn).has(event.target).length) {
          $(pinContent).hide();
        }    
      }); 
    }

    function customCheckboxBinding() {
      var label = '[data-checkbox]', 
          $checkbox = $(label).find('input');

      $checkbox.on('change', function(event) {  
        if ($(this).attr('checked') !== 'checked' || typeof $(this).attr('checked') === 'undefined') { 
          $(this).attr('checked', true);   
          $(this).parent().addClass('checked');

          toggleOrderPinContent(false);
        } else {
          $(this).attr('checked', false);  
          $(this).parent().removeClass('checked');

          toggleOrderPinContent(true);
        }
      }); 
    }

    function customRadioBinding() {
      var label = '[data-radio]',
          $radio = $(label).find('input[type="radio"]');

      $radio.on('change', function(event) {
        if ($(this).attr('checked') !== 'checked' || typeof $(this).attr('checked') === 'undefined') {
          $(label).find('input[type="radio"]').attr('checked', false);
          $(label).removeClass('checked'); 

          $(this).attr('checked', true); 
          $(this).parent().addClass('checked');
        }
      });      
    }

    function toggleOrderPinContent(value) {
      var $content = $('[data-checkbox-triggered-content]');

      value ? $content.hide() : $content.show();      
    }

    function filterWidgetToggleBinding() {
      var trigger = '[data-open-filter-trigger]',
          filter = '[data-filter]';

      $(document).on('click', trigger, function(event) {
        $(this).closest('.sticky-widget').find(filter).toggleClass('opened');

        event.preventDefault();
      });    
    }

    function datepickerInit() {
      var $datepicker = $('[data-datepicker]');

      if ($datepicker.length) {
        $datepicker.datepicker({
          orientation: "bottom left"
        });
      }
    }

    function topbarButtonsBinding() {
      var trigger = '[data-topbar-dropdown-trigger]';

      $(document).on('click', trigger, function (event) {
        $(this).toggleClass('active');
        event.preventDefault();
      });    
    }

    function customInputFile() {
      // Browser supports HTML5 multiple file?
      var multipleSupport = typeof $('<input/>')[0].multiple !== 'undefined',
          isIE = /msie/i.test(navigator.userAgent);

      $.fn.customFile = function() {
        return this.each(function() {
          var $file = $(this).addClass('custom-file-upload-hidden'), // the original file input
              $wrap = $('<div class="file-upload-wrapper">'),
              $input = $('<input type="text" class="file-upload-input" placeholder="No Files Uploaded" />'),
              // Button that will be used in non-IE browsers
              $button = $('<button type="button" class="btn theme-btn_sm btn-dark pull-left file-upload-btn">Choose</button>'),
              // Hack for IE
              $label = $('<label class="btn theme-btn_sm btn-dark pull-left file-upload-btn" for="'+ $file[0].id +'">Choose</label>');

          // Hide by shifting to the left so we
          // can still trigger events
          $file.css({
            position: 'absolute',
            left: '-9999px'
          });

          $wrap
            .insertAfter($file)
            .append($file, (isIE ? $label : $button), $input);

          // Prevent focus
          $file.attr('tabIndex', -1);
          $button.attr('tabIndex', -1);

          $button.click(function () {
            $file.focus().click(); // Open dialog
          });

          $file.change(function() {
            var files = [], fileArr, filename;

            // If multiple is supported then extract
            // all filenames from the file array
            if (multipleSupport) {
              fileArr = $file[0].files;
              for (var i = 0, len = fileArr.length; i < len; i++) {
                files.push(fileArr[i].name);
              }
              filename = files.join(', ');

            // If not supported then just take the value
            // and remove the path to just show the filename
            } else {
              filename = $file.val().split('\\').pop();
            }

            $input
              .val(filename) // Set the value
              .attr('title', filename) // Show filename in title tootlip
              .focus(); // Regain focus

          });

          $input.on({
            blur: function() { 
              $file.trigger('blur'); 
            },
            keydown: function(event) {
              if (event.which === 13) { // Enter
                if (!isIE) { 
                  $file.trigger('click'); 
                }
              } else if (event.which === 8 || event.which === 46) { // Backspace & Del
                // On some browsers the value is read-only
                // with this trick we remove the old input and add
                // a clean clone with all the original events attached
                $file.replaceWith($file = $file.clone(true));
                $file.trigger('change');
                $input.val('');
              } else if (event.which === 9) { // TAB
                return;
              } else { // All other keys
                return false;
              }
            }
          });
        });
      };

      // Old browser fallback
      if (!multipleSupport) {
        $(document).on('change', 'input.customfile', function() {

          var $this = $(this),
              // Create a unique ID so we
              // can attach the label to the input
              uniqId = 'customfile_'+ (new Date()).getTime(),
              $wrap = $this.parent(),
              // Filter empty input
              $inputs = $wrap.siblings().find('.file-upload-input')
                                        .filter(function(){ return !this.value }),
              $file = $('<input type="file" id="'+ uniqId +'" name="'+ $this.attr('name') +'"/>');

          // 1ms timeout so it runs after all other events
          // that modify the value have triggered
          setTimeout(function() {
            // Add a new input
            if ($this.val()) {
              // Check for empty fields to prevent
              // creating new inputs when changing files
              if (!$inputs.length) {
                $wrap.after($file);
                $file.customFile();
              }
            // Remove and reorganize inputs
            } else {
              $inputs.parent().remove();
              // Move the input so it's always last on the list
              $wrap.appendTo($wrap.parent());
              $wrap.find('input').focus();
            }
          }, 1);

        });
      }
    }

    function customInputFileInit() {
      customInputFile();  
      $('input[type=file]').customFile();
    }

    return app;
  };

  oroCommerce.init();

})(jQuery, _);