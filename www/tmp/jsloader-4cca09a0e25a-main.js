
$(function(){
    $('.carousel').carousel({
        interval: 5000 //changes the speed
    })

    $(".menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });

	$(".starDrawer").trigger ( "change" );

	$('[data-toggle="popover"]').popover( {
		//trigger: 'click focus'
	} );

	/*$("tr[data-href]").each(function (i){
		$(this).click( function (e) {
			e.stopPropagation();
			e.preventDefault();
			document.location = $(this).data("href")
		});
		$(this).css("cursor", "pointer");
	});*/


	$("[data-toggle=location]").each ( function () {
		var src = $(this).data('src') || $(this).attr("href") || $(this).attr('src');
		if(src) {
			$(this).click( function (e) {
				e.preventDefault();
				if (navigator.geolocation) {
			        navigator.geolocation.getCurrentPosition(function ( position ) {
						
						$.nette.ajax ({ 
							url: src + "&lat=" + position.coords.latitude + "&lng=" + position.coords.longitude,
						},
						$('body'),
						jQuery.Event( "click" )) . error ( function () {
							var $div = $("div.info") ? $("div.info") : $("<div class='info'></div>").prependTo($("body"))
							$div.removeClass()
							$div.addClass("info alert alert-danger")
							$div.empty();
							$div.html("Nepodařilo se kontaktovat server.")
						});

			        }, function ( error ) {
						var $div = $("div.info") ? $("div.info") : $("<div class='info'></div>").prependTo($("body"))
						$div.removeClass()
						$div.addClass("info alert alert-danger")
						$div.empty();
						$div.html("Nepodařilo se zjistit vaší polohu.")
			        });
			    } else { 
					var $div = $("div.info") ? $("div.info") : $("<div class='info'></div>").prependTo($("body"))
					$div.removeClass()
					$div.addClass("info alert alert-danger")
					$div.empty();
					$div.html("Váš prohlížeč nepodporuje získání polohy.")
			    }	
			});
		}


	});


	var ratingForm = function () {
		$(".ratingForm .form-group").each ( function () {
			var $that = $(this)
			/*
			 * handle range radios
			 */
			if($(this).find('input[type=radio].range').length) {
				// correct with radio buttons
				// console.log($(this));
				var radios = $(this).find('input[type=radio].range');
				var $parent = $(radios[0]).parent().parent();
				var imageUrl = "http://localhost/publist/images/stars/star.png";
				/*radios . each ( function ( index ) {
					$parent.append( "<img src='" + imageUrl + "' class='star_25'>" );
				});*/
				//$parent.addClass('btn-group')
				//$parent.attr('data-toggle', 'buttons');

				// first radio is no rating
				
				var $firstRadio = $(radios[0])
				if($firstRadio.hasClass('optional')) {
					$firstRadio.parent().addClass('btn btn-default btn-rating')

					if($firstRadio.is(":checked")){
						$firstRadio.parent().addClass('active');
						//$firstRadio.parent().text('Nehodnotit');
					}

					$firstRadio.parent().click(function (){
						$(this).siblings().removeClass('active')
						$(this).addClass('active')
					});

					$firstRadio.hide()

					radios = radios.splice( 1, radios.length-1 );
				}



				// others are stars-rating
				$.each ( radios, function () {
					var $label = $(this).parent()
					$label.addClass('btn-star')

					var inHandler = function (e) {
						$(this).siblings().removeClass('active');
						$(this).prevAll().not('.btn-rating').addClass('active');
						//$(this).parent().find('.btn-rating').text('Hodnotit');
						$(this).addClass('active')
					};

					var outHandler = function (e) {
						$(this).parent().find("input[type=radio].range:checked").parent().click()
					};

					$label.click(inHandler);

					$label.hover(inHandler, outHandler);

					if($(this).is(":checked")) {
						$label.trigger('click')
					}

					$(this).hide()
					$label.html($label.children());

				});
			}

			if($(this).find('input[type=checkbox].switch').length) {
				$(this).find('input[type=checkbox].switch').bootstrapSwitch();
			}

			if($(this).find('select.value').length) {
				var $selects = $(this).find('select.value');
				$selects.change( function (e) {
					$(this).parent().parent().parent().find('.text').html($(this).find(":selected").text())
				});
				$.each($selects, function () {
					if($(this).find(":selected").val() != 0)
						$(this).parent().parent().parent().find('.text').html($(this).find(":selected").text())
				})
			}

		});
	};


	$.nette.ext({
	  load: ratingForm,
	  init: ratingForm
	});

	var pubForm = function () {
		if ( ! $(document).find('.pubForm').length )
			return;

		var capitalize = function (s){
		    return s.toLowerCase().replace( /(?:^|\s)\S/g, function(a){ return a.toUpperCase(); } );
		};
		
		var enabled = true

		var $txt = $("#pubForm-name");
		var $slc = $("#pubForm-type");
		var $enb = $("#pubForm-enable")


		var handler = function ( e ) {
			
			if ( !enabled )
				return;

			var data = $slc. data( 'types' )

			$.each( data, function ( i, val ) {
				var reg = new RegExp(val, 'i');
				if( reg.test($txt.val()) ){
					var selected = $slc.find('option').filter(function () { return $(this).html() == i; }).val();
					$slc.val(selected)
					var reg2 = new RegExp ( "^" + val, 'i' )
					if (e && e.type != "change"){
						if ( (e . which == 32 || 
							  e . which == 13 || 
							  e . which == 10) && reg2.test($txt.val() ) ) {
							$txt.val( $txt.val().replace( reg2, "" ) );
						}
					} else {
						$txt.val( $txt.val().replace( reg2, "" ) );
					}
				}
			});

			$txt.val( capitalize ( $txt.val ()  ) );
		};

		handler(null);
		$txt.keyup ( handler );
		$txt.change ( handler );

		$enb.click(function (e) {
			$(this).html( enabled ? 'Povolit našeptávání' : 'Zakázat našeptávání' )
			enabled = enabled ? false : true;
			e.preventDefault();
		});
	};

	$.nette.ext({
	  load: pubForm,
	  //init: pubForm
	});

	pubForm();





/*
<div class="btn-group" data-toggle="buttons">
  <label class="btn btn-primary active">
    <input type="radio" name="options" id="option1" autocomplete="off" checked> Radio 1 (preselected)
  </label>
  <label class="btn btn-primary">
    <input type="radio" name="options" id="option2" autocomplete="off"> Radio 2
  </label>
  <label class="btn btn-primary">
    <input type="radio" name="options" id="option3" autocomplete="off"> Radio 3
  </label>
</div>
*/


});


$(window).load(function () { $.nette.init(); });


$.nette.ext('unique', null);

(function($, undefined) {

	$.nette.ext({
	  before: function (xhr, settings) {
	    var question = settings.nette.el.data('confirm');
	    if (question) {
	      return confirm(question);
	    }
	  }
	});

	$.nette.ext('ajax-operations',
	{
	  init: function () 
	  {
	  },
	  before: function ( xhr, settings )
	  {
	  },
	  complete: function ()
	  {
	      $(".noshow").css("display", "none"); 
	      //$.nette.load();
	  }
	});



})(jQuery);


