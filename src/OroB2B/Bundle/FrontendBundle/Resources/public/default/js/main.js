require(['jquery', 'lodash', 'slick', 'raty', 'perfectScrollbar', 'fastclick', 'elevatezoom'], function(
    jQuery,
    _,
    slick,
    raty,
    perfectScrollbar,
    FastClick,
    elevateZoom
) {

    (function($) {
        'use strict';

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
                oroMPAccordionBinding(); //Multi pupose accordion toggling binding function
                oroExpandLongTextBinding(); //Multi pupose long text toggling binding function
                tabToggleBinding(); //Tabs toggling binding function
                ratyInit(); //Initing function of raty plugin
                stickyNavigationInit(); // Init mobile sticky navigation
                ieDetectInit(); //InterNet Explorer detecting function
                mobileNavigationButtonsBinding(); //Mobile navigation button binding
                clickElseWhereBinding(); //Click outside the container binding
                toggleNestedListBinding(); //Toggling of nested lists binding
                heroSliderInit(); //Initing of hero slider (fading slick slider)
                productsSliderInit(); //Initing of products slider (slick slider)
                moreInfoExpandBinding(); //More info button binding
                pinClickBinding(); //Pin click binding
                filterWidgetToggleBinding(); //Filter Widget toggle binding
                topbarButtonsBinding(); //Topbar buttons clicks binding
                salesPanelToggleInit(); //Sales panel toggle binding
                flexSelectResizeInit(); //resizing the selects
                salesMobileNavMenuInit(); //sales mobile navigation
                stickyCatalogHeaderInit(); //sticky catalog, cart header
                avoidDropdwonMenuHideInit();
                dropdownMenuHideByCloseBtnBinding();
                customScrollbarInit();
                elevateZoomInit();

                countInit().init({
                    plus: '[data-count-plus]',
                    minus: '[data-count-minus]',
                    inputNum: '[data-count-input]'
                });

                //On page load init list view catalog
                catalogViewTemplatingInit(catalogViews.list);

                //fast click for mobile devices
                FastClick.attach(document.body);
            };

            function sidebarToggleBinding() {
                var btn = '[data-sidebar-trigger]';

                $(document).on('click', btn, function(event) {
                    $(this)
                        .toggleClass('active')
                        .closest('[data-collapse-sidebar]').toggleClass('sidebar_collapsed')
                        .end()
                        .closest('.main').find('[data-collapse-content]').toggleClass('content_sidebar-collapsed')
                        .end()
                        .closest('.wrapper').find('[data-collapse-search]').toggleClass('search-section_sidebar-collapsed');


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

            function oroMPAccordionBinding() {
                var trigger = '[data-oro-mpa-trigger]';

                $(document).on('click', trigger, function(event) {
                    var $widget = $(this).closest('[data-oro-mpa-widget]'),
                        $container = $widget.find('[data-oro-mpa-container]');

                    $widget.toggleClass('oro-mp-widget__list-expanded');
                    $container.toggleClass('hidden');

                    event.preventDefault();
                });
            }

            function oroExpandLongTextBinding() {
                var trigger = '[data-oro-expand-trigger]';

                $(trigger).each(function () {
                    var $widget = $(this).closest('[data-oro-expand-widget]'),
                        $container = $widget.find('[data-oro-expand-container]'),
                        content = $container.html(),
                        showTotalChar = $container.attr('max-length');
                    if (content.length > showTotalChar) {
                        var short = content.substr(0, showTotalChar),
                            newContent = '<span data-oro-expand-short>' + short + '...' + '</span>';

                        newContent += '<span class="hidden" data-oro-expand-long>' + content + '</span>';
                        $container.html(newContent);
                    }
                });

                $(document).on('click', trigger, function(event) {
                    var $widget = $(this).closest('[data-oro-expand-widget]'),
                        $container = $widget.find('[data-oro-expand-container]'),
                        $shortText = $container.find('[data-oro-expand-short]'),
                        $longText = $container.find('[data-oro-expand-long]');

                    $widget.toggleClass('oro-long-text-expanded');
                    $shortText.toggleClass('hidden');
                    $longText.toggleClass('hidden');

                    event.preventDefault();
                });
            }

            function tabToggleBinding() {
                var tabTrigger = '[data-tab-trigger]',
                    horizontalTab = '[data-horizontal-tab]';

                $(document).on('click', tabTrigger, function(event) {
                    var isHorizontal = $(this).data('tab-type-horizontal') || false,
                        tabId = $(this).data('tab-trigger');

                    if (isHorizontal) {
                        $(horizontalTab).removeClass('active');
                        $(tabTrigger).closest('[data-tab-nav-list]').find('li').removeClass('active');

                        $(this).parent().addClass('active');

                        $(horizontalTab).each(function() {
                            if ($(this).data('horizontal-tab') === tabId) {
                                $(this).addClass('active');
                            }
                        });

                    } else {
                        if ($(this).closest('[data-tab]').hasClass('active')) {
                            $(this).closest('[data-tab]').toggleClass('active');
                        } else {
                            $('[data-tab]').removeClass('active');
                            $(this).closest('[data-tab]').addClass('active');
                        }
                    }

                    //Reinitializing of products carousel
                    if ($('[data-products-slider]').length) {
                       $('[data-products-slider]').slick('unslick');
                       productsSliderInit();
                    }

                    $(this).trigger('tab:toggle');
                    event.stopPropagation();
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

                $(document).on('click', btn, function(event) {
                    //Switch on current target button and container
                    if ($(this).attr('data-open') === 'true') {
                        $(this).attr('data-open', false);
                        $(this).removeClass('active');
                        $(this).find(container).removeClass('active');

                        //Show browser native srcollbar
                        $('body.hidden-scrollbar').removeClass('hidden-scrollbar').css('overflow-y', 'auto');
                    } else {
                        //Switch off all other navigation buttons, and containers
                        $(btn).attr('data-open', false);
                        $(btn).removeClass('active');
                        $(container).removeClass('active');

                        $(this).attr('data-open', true);
                        $(this).addClass('active');
                        $(this).find(container).addClass('active');

                        //Hide browser native srcollbar
                        $('body').addClass('hidden-scrollbar').css('overflow-y', 'hidden');
                    }

                    //Resize drwopdown according to window height
                    mobileNavigitionDropdownToWindowSize('[data-mobile-navigation-dropdown]');

                    event.preventDefault();
                    event.stopPropagation();
                    window.event.cancelBubble = true;
                });
            }

            function clickElseWhereBinding() {
                var $btn = $('[data-trigger-mobile-navigation]'),
                    $container = $('[data-mobile-navigation-dropdown]');

                $(document).on('click', function(event) {
                    if (!$container.is(event.target) && $container.has(event.target).length === 0 && !$btn.is(event.target) && !$btn.has(event.target).length) {
                        //Mobile navigation item
                        $('body.hidden-scrollbar').removeClass('hidden-scrollbar').css('overflow-y', 'auto');
                        $btn.attr('data-open', false);
                        $btn.removeClass('active');
                        $container.removeClass('active');

                        //Sales mode mobile navigation item
                        $container.css('max-height', 'auto');
                        $container.css('transform', 'translateX(0)');
                        $('[data-sales-m-navigation-level]').removeClass('active');
                    }
                });
            }

            function mobileNavigitionDropdownToWindowSize(container) {
                var $win = $(window),
                    $nav = $('[data-sticky-navigation]'),
                    $middleBar = $('.middlebar'),
                    middleBarHeight = $middleBar.outerHeight(),
                    winHeight = $win.outerHeight(),
                    navHeight = $nav.outerHeight(),
                    $container = $(container);

                if ($nav.hasClass('sticky')) {
                    $container.css('max-height', winHeight - navHeight);
                } else {
                    $container.css('max-height', winHeight - navHeight - middleBarHeight);
                }
            }

            function toggleNestedListBinding() {
                var trigger = '[data-trigger-nested-list]',
                    $list = $('[data-nested-list]');

                $(document).on('click', trigger, function(event) {
                    $(trigger).toggleClass('expanded');
                    $list.toggleClass('expanded');

                    event.preventDefault();
                });
            }

            function heroSliderInit() {
                var $hero = $('[data-hero-slider]');

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
                    $(this).parent().find('i').toggleClass('expanded');
                    $(this).parent().find('[data-more-info]').slideToggle();

                    event.preventDefault();
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

                            event.preventDefault();
                        });
                    }
                };

                Count.minusEvent = {
                    click: function() {
                        var self = this;

                        $(document).on('click', self.minus, function(event) {
                            var $input = $(this).parent().find(self.inputNum),
                                current = $input.val();

                            if (current > 1) {
                                current--;
                            } else {
                                return false;
                            }

                            $input.val(current);

                            event.preventDefault();
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

            function filterWidgetToggleBinding() {
                var trigger = '[data-open-filter-trigger]',
                    filter = '[data-filter]';

                $(document).on('click', trigger, function(event) {
                    $(this).closest('.sticky-widget').find(filter).toggleClass('opened');

                    if ($(this).closest('.sticky-widget').find(filter).hasClass('opened')) {
                        $(this).removeClass('collapsed');
                    } else {
                        $(this).addClass('collapsed')
                    }

                    event.preventDefault();
                });
            }

            function topbarButtonsBinding() {
                var trigger = '[data-topbar-dropdown-trigger]';

                $(document).on('click', trigger, function (event) {
                    $(this).toggleClass('active');
                    event.preventDefault();
                });
            }

            function salesPanelToggleInit() {
                var handle = '[data-sales-panel-toggle]',
                    $panel = $('[data-sales-panel]'),
                    $wrapper = $('[data-wrapper]'),
                    $stickyHeader =  $('[data-sticked]'),
                    expanded = false;

                setPanelHeight();

                $(document).on('click', handle, function(event) {

                    if (expanded) {
                        $panel.css('transform', 'translate3d(0px, ' + -calculatePanelHeight() + 'px, 0px)');
                        $stickyHeader.removeAttr('style');
                        $wrapper.css('transform', 'translate3d(0px, 0px, 0px)');
                        expanded = false;
                    } else {
                        $panel.css('transform', 'translate3d(0px, 0px, 0px)');
                        $stickyHeader.css('transform', 'translate3d(0px, ' + calculatePanelHeight() + 'px, 0px)');
                        $wrapper.css('transform', 'translate3d(0px, ' + calculatePanelHeight() + 'px, 0px)');
                        expanded = true;
                    }

                    event.preventDefault();
                });

                $(window).on('resize', _.throttle(setPanelHeight, 100));
            }

            //Helper functions to calculate panel height for salesPanelToggleInit function
            function calculatePanelHeight() {
                var $panel = $('[data-sales-panel]'),
                    $panelTop = $('.sales-panel__top'),
                    $panelBottom = $('.sales-panel__bottom'),
                    panelTopHeight = $panelTop.outerHeight(),
                    panelBottomHeight = $panelBottom.outerHeight(),
                    panelHeight;

                if (window.innerWidth >= 992) {
                    panelHeight = panelTopHeight + panelBottomHeight - 11;
                } else {
                    panelHeight = 130;
                }

                return panelHeight;
            }

            function setPanelHeight() {
                var $panel = $('[data-sales-panel]'),
                    $wrapper = $('[data-wrapper]'),
                    $stickyHeader =  $('[data-sticked]');

                $panel.css('transform', 'translate3d(0px, ' + -calculatePanelHeight() + 'px, 0px)');
                $stickyHeader.removeAttr('style');

                if (window.innerWidth >= 992) {
                    $wrapper.addClass('sales-mode');
                    $wrapper.css('transform', 'translate3d(0px, 0px, 0px)');
                } else {
                    $wrapper.removeAttr('style').removeClass('sales-mode');
                }
            }

            function flexSelectResizeInit() {
                calculateSelectSize();

                $(window).on('resize', _.debounce(calculateSelectSize, 100));
            }

            //Helper function for the flexSelectInit function
            function calculateSelectSize() {
                var select = '[data-flex-select]',
                    winWidth = window.innerWidth;

                if (winWidth >= 992 && winWidth <= 1400) {
                    $(select).css('width', ((winWidth - 175 - 105) / 6) + 'px');
                }
            }

            function salesMobileNavMenuInit() {
                var btn = '[data-sales-mobile-navigation-trigger]',
                    back = '[data-sales-m-back]';

                $(document).on('click', btn, function(event) {
                    var type = $(this).data('sales-mobile-navigation-trigger'),
                        level = $(this).closest('[data-sales-m-navigation-level]').data('level');

                    mobileNavigitionDropdownToWindowSize('[data-sales-m-navigation-level]');
                    $('body').addClass('hidden-scrollbar').css('overflow-y', 'hidden');

                    $(this).closest('[data-sales-m-navigation-level]').css('transform', 'translateX(-100%)');
                    $('[data-sales-m-navigation-level="' + type + '"]').addClass('active');

                    event.preventDefault();
                    event.stopPropagation();
                    window.event.cancelBubble = true;
                });

                $(document).on('click', back, function(event) {
                    var type = $(this).data('sales-m-back');

                    $('[data-sales-m-navigation-level="' + type + '"]').removeAttr('style');
                    $(this).closest('[data-sales-m-navigation-level]').removeClass('active');

                    event.preventDefault();
                    event.stopPropagation();
                    window.event.cancelBubble = true;
                });
            }

            function stickyCatalogHeaderInit() {
                var sticked = '[data-sticked]',
                    $header = $('header'),
                    headerHeight = $header.height() - $('.top-nav').height(),
                    $win = $(window);

                $win.on('scroll', function(event) {
                    if ($win.scrollTop() > headerHeight) {
                        $(sticked).addClass('sticked');
                    } else {
                        $(sticked).removeClass('sticked');
                    }
                });
            }

            function avoidDropdwonMenuHideInit() {
                $(document).on('click', '.dropdown-menu', function(event) {
                    event.stopPropagation();
                });

                $(document).on('click', '.navigation_mobile__dropdown-container', function(event) {
                    event.stopPropagation();
                });

                $('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });
                //To fix touch event with dropdown-menu: http://stackoverflow.com/questions/17435359/bootstrap-collapsed-menu-links-not-working-on-mobile-devices/17440942#17440942
            }

            function dropdownMenuHideByCloseBtnBinding() {
                $(document).on('click', '.close', function() {
                    $(this)
                        .closest('.dropdown-menu')
                        .parent()
                        .toggleClass('open')
                        .trigger('hide.bs.dropdown');
                });
            }

            function customScrollbarInit() {
                $('.columnsSettings').each(function() {
                    $(this).perfectScrollbar();
                });

                $(document).on('click', '.oro-oq__sorting-settings', function() {
                    $('.columnsSettings').each(function() {
                        $(this).perfectScrollbar('update');
                    });
                });
            }

            function elevateZoomInit() {
                $('[data-zoom-image]').elevateZoom({
                    scrollZoom: true,
                    zoomWindowWidth: 630,
                    zoomWindowHeight: 376,
                    borderSize: 1,
                    borderColour: '#ebebeb',
                    lensBorderColour: '#7d7d7d',
                    lensColour: '#000',
                    lensOpacity: 0.22
                });
            }

            return app;
        };

        oroCommerce.init();
    })(jQuery);

});
