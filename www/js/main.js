$(function(){
    $('.carousel').carousel({
        interval: 5000 //changes the speed
    })

    $(".menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });


    $(window).on('swiperight', function (e) {
        e.preventDefault();
        if(! $("#wrapper").hasClass('toggled')) {
	        $("#wrapper").toggleClass("toggled");
        }
    });

    $(window).on('swipeleft', function (e) {
        e.preventDefault();
        if($("#wrapper").hasClass('toggled')) {
	        $("#wrapper").toggleClass("toggled");
        }
    });


	$('[data-toggle="popover"]').popover( {
		//trigger: 'click focus'
	} );


    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
        $('.selectpicker').selectpicker('mobile');
    } else {
		$(".selectpicker").selectpicker();
    }

	/*$("tr[data-href]").each(function (i){
		$(this).click( function (e) {
			e.stopPropagation();
			e.preventDefault();
			document.location = $(this).data("href")
		});
		$(this).css("cursor", "pointer");
	});*/

	

	$("#update-pub").click ( function (e) {
		e . preventDefault ();
		var lat = $(".gpspicker-lat").val()
		var lng = $(".gpspicker-lng").val()

		var pub_inputs = {
			'whole_name': $('.pubForm #pubForm-whole_name'),
			'long_name': $('.pubForm #pubForm-long_name'),
			'name': $('.pubForm #pubForm-name'),
			'opening_hours': $('.pubForm #frm-pubForm-opening_hours'),
			'website': $('.pubForm #frm-pubForm-website'),
			'lat': $('.pubForm #frm-pubForm-coords-lat'),
			'lng': $('.pubForm #frm-pubForm-coords-lng'),
			'location': $('.pubForm #frm-pubForm-coords-location'),
			'address': $('.pubForm #frm-pubForm-coords-address'),
		}

		var $info = $(".info-pub");
		$info . addMessage = function ( txt, lvl ) {
			this.append('<li class="alert alert-' + lvl + ' list-group-item">' + 
				        	txt +
				        '</li>')
		}
		var $panelGroup = $("#results-pub");
		var $modalContent = $("#modal-pub .details")
		var $modal = $("#modal-pub")

	
		var request = {
			location: new google.maps.LatLng ( lat, lng ),
			types: ['food', 'restaurant', 'bar', 'cafe' ],
			rankBy: google.maps.places.RankBy.DISTANCE,
			language: 'cs'
		};

		service = new google.maps.places.PlacesService(window.NetteGpsPicker.map);
  		service.nearbySearch(request, function ( results, status ) {
	
			if ( status == google.maps.places.PlacesServiceStatus.OK )
			{
				$panelGroup.empty()
				for ( var i = 0; i < results . length; i ++ )
				{
					var $panel = $("<div class='panel panel-default'></div>")
					var $heading = $("<div class='panel-heading'></div>")
					var $anchor = $("<a href='#' data-place='" + results[i].place_id + "' class='result-link-pub'>" +
						            "<h4>" + results[i] . name + "</h4>" +
								    "<small>" + results[i].vicinity + "</small>" +
						            "</a>")
					$heading.append($anchor)
					$panel.append($heading)
					$panelGroup.append($panel)
					$modal.modal('show')
				}

				$(".result-link-pub").click ( function (e) {
					$info . empty ();
					e . preventDefault ();
					//console.log($(e.target.closest('a')).data('place'))
					service.getDetails( { placeId: $(e.target.closest('a')).data('place') }, function (place, status) {
						//console.log(status)
						if (status == google.maps.places.PlacesServiceStatus.OK)
						{
							var geocoder = new google.maps.Geocoder();
							geocoder.geocode({'address': place.formatted_address}, function(results, status) {
								var address = null
								var location = null
							    if (status == google.maps.GeocoderStatus.OK) {
							      if (results.length && results[0].geometry) {
							      	if (results[0].geometry.location_type == 'APPROXIMATE') {
							      		$info.addMessage ( "Lokace je velmi nepřesná, pokuste se zadat přesnější adresu.", 'warning' );
							      	} else if (results[0].geometry.location_type == 'ROOFTOP') {
							      		$info.addMessage ( "Nalezena!", 'success' );
							      	}

							        address = results[0].formatted_address
									var latlng = results[0].geometry.location

									var parts2 = window.NetteGpsPicker.parseAddressComponents ( results );

									location = parts2.length ? parts2.join(", ") : address;
									if(!parts2.length){
							      		$info.addMessage ( "Lokalita nebyla nastavena (výchozí nastavení je stejné jako adresa), zkuste prosím zadat přesnější adresu.", 'warning', "text" );
									}


									$modalContent . empty();
									var $dl = $("<dl></dl>")
									$dl.append ("<dt>Název</dt><dd>" + place.name + "</dd>");
									$dl.append ("<dt>Webová stránka</dt><dd>" + place.website + "</dd>");
									$dl.append ("<dt>Adresa</dt><dd>" + address + "</dd>");
									$dl.append ("<dt>Lokace</dt><dd>" + location + "</dd>");
									$dl.append ("<dt>Latitude</dt><dd>" + latlng.lat() + "</dd>");
									$dl.append ("<dt>Longitude</dt><dd>" + latlng.lng() + "</dd>");
									if (place.opening_hours && place.opening_hours.weekday_text)	
									$dl.append ("<dt>Otevírací hodiny</dt><dd>" + place.opening_hours.weekday_text.join('<br />') + "</dd>");
									$modalContent.append("<h3>Získané informace</h3>")
									$modalContent.append($dl)
									$btn = $("<a href='#' class='btn btn-success form-control'>Update</a>").click(function(e){
										e.preventDefault()
										pub_inputs['whole_name'].val(place.name).change();
										//pub_inputs['long_name'].val(place.name).change();
										pub_inputs['website'].val(place.website).change();
										//pub_inputs['address'].val(address).change();
										//pub_inputs['location'].val(location).change();
										//pub_inputs['lat'].val(latlng.lat()).change();						
										//pub_inputs['lng'].val(latlng.lng()).change();	
										if (place.opening_hours && place.opening_hours.weekday_text)			
											pub_inputs['opening_hours'].val(place.opening_hours.weekday_text.join('\n')).change();

										window.NetteGpsPicker.shape.marker.setPosition(latlng);
										window.NetteGpsPicker.shape.marker.setMap(window.NetteGpsPicker.map);
										window.NetteGpsPicker.shape.marker.map.setCenter(latlng);			
										window.NetteGpsPicker.shape.fill(latlng.lat(), latlng.lng(), location, address);

										$modal.modal('hide')	
										
									})
									$modalContent.append($btn)
									$modalContent.append("<hr />")

							      } else {
						      		$info.addMessage ( "Zadaná adresa neexistuje!", 'danger' );			      	
							      }
								} else {
						      		$info.addMessage ( "Zadaná adresa neexistuje!", 'danger' );			      	
								}

							 });
						}
						else if ( status == google.maps.places.PlacesServiceStatus.ZERO_RESULTS )
							$info.addMessage ( 'Žádné výsledky nenalezeny, bohužel.', 'warning' )
						else 
							$info.addMessage ( 'Nepodařilo se kontaktovat Google na pozadí. Opakujte prosím.', 'danger' )


							
					});
				})
/*

<div class="panel-group" id="accordion">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
				Kliknutím na vybranou restauraci přejdete na stránku pro její vložení
            </h4>
        </div>
    </div>
    <div n:foreach="$pubs as $pub" class="panel panel-default">
        <div class="panel-heading accordion-toggle" data-toggle="collapse" data-parent="#accordion" data-target="#collapse-{$pub->id}">
        	<a href="{link this type => $presenter::SELECT_CLOSEST, step => 2, placeid => $pub -> id}">
                <h4 class="panel-title">
					{$iterator->counter}.
                    {$pub->wholeName} <small>{$pub->location}</small>
                    <span class="linebreak">
                    	<small>Vzdálenost:</small> {$pub->distance|metres}
                   	</span>
                </h4>
            </a>
        </div>
    </div>
</div>

*/				
			}
			else if ( status == google.maps.places.PlacesServiceStatus.ZERO_RESULTS )
				$info.addMessage ( 'Žádné výsledky nenalezeny, bohužel.', 'warning' )
			else 
				$info.addMessage ( 'Nepodařilo se kontaktovat Google na pozadí. Opakujte prosím.', 'danger' )

  		});

	});


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

		if($(this).data('trigger') && $(this).data('trigger') == 'auto') {
			$(this).click();
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
					$(this).parent().parent().parent().find('.text').eq(0).html($(this).find(":selected").text())
				});
				$.each($selects, function () {
					if($(this).find(":selected").val() != 0)
						$(this).parent().parent().parent().find('.text').eq(0).html($(this).find(":selected").text())
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
		    return s.toLowerCase().replace( /((?:\s)(?!a\s|i\s|&\s|v\s|se\s|nebo\s|na\s|ve\s)\S|(?:^)\S)/g, function(a){ return a.toUpperCase(); } );
		};
		
		var enabled = true
		var $whl = $("#pubForm-whole_name");
		var $txt = $("#pubForm-name");
		var $slc = $("#pubForm-type");
		var $enb = $("#pubForm-enable")
		var $form = $whl.closest('form');


		var handler = function ( e ) {
			
			if ( !enabled )
				return;

			var data = $slc. data( 'types' )

			$slc.selectpicker('deselectAll')

			// find all types
			var values = [];
			$.each( data, function ( i, val ) {
				var reg = new RegExp(val, 'i');
				if( reg.test($whl.val()) ){
					var $options = $slc.find('option').filter(function () { return $(this).html() == i; });
					$.each($options, function () {
						values.push($options.val())
					});
				}
			});

			// remove types from name
			var cont = true
			var cnt = Object.keys(data).length
			while ( cont && cnt > 0 && $whl.val() ) {
				//console.log($txt.val())
				$.each( data, function ( i, val ) {
					var reg = new RegExp(val, 'i');
					if( reg.test($whl.val()) ){
						var reg2 = new RegExp ( "^" + val + "(\\s*(a|&|and|i)?\\s+)?", 'i' )
						//console.log("^" + val + "(\\s*(a|&|and|i)?\\s+)?")
						// remove types from name
						if (e && e.type != "change"){
							if ( (e . which == 32 || 
								  e . which == 13 || 
								  e . which == 10) && reg2.test($txt.val() ) ) {

								if ( $txt.val() == $txt.val().replace( reg2, "" ) ) {
									cont = false;
									return;
								}
								$txt.val($txt.val().replace( reg2, "" )).change();
							}
						} else {
							//console.log($txt.val())
							//console.log($txt.val().replace( reg2, "" ))
							if ( $txt.val() == $txt.val().replace( reg2, "" ) ){
								cont = false;
								return;
							}
							$txt.val($txt.val().replace( reg2, "" )).change();
							//console.log("set to: "+$txt.val())
						}
					}
				});
				cnt --;
				//console.log("one while")
			}
			// set corresponding values
			$slc.selectpicker('val', values )
			// capitalize text in txt
			$txt.val( capitalize ( $txt.val () ) );
		};
		if(! ( $form.data('type') && $form.data('type') == 'adminForm' ) ) {
			handler(null);
		}
		$txt.keyup ( handler );
		$txt.change ( handler );

		$whl.keyup(function(e) {

			if ( !enabled )
				return;

			$whl.val( capitalize ( $whl.val ()  ) );
			$txt.val( capitalize ( $whl.val () ) ).change();
		}).change(function(e) {

			if ( !enabled )
				return;

			$whl.val( capitalize ( $whl.val ()  ) );
			$txt.val( capitalize ( $whl.val () ) ).change();
		});

		$enb.click(function (e) {
			$(this).html( enabled ? 'Povolit našeptávání' : 'Zakázat našeptávání' )
			enabled = enabled ? false : true;
			if (enabled) {
				handler (null);
			}
			e.preventDefault();
		});

		var $latInput = $("#alternatePubForm-lat");
		var $lngInput = $("#alternatePubForm-lng");

		$("#alternatePubForm-lat").change(function(e){
			var $input = $(this);
			var val = $input.val()

			var number = function ( deg, min, sec ) {
				if (!deg) {
					return null;
				}
				var fractional_part = ((min * 60) + sec) / 3600;
				if (deg < 0) {
					deg = deg - fractional_part;
				} else {
					deg = deg + fractional_part;
				}
				return deg;
			}

			var decimal = function ( str ) {
				if ( ! /°/.test(str) )
					return str;

				var degrees = null;
				var minutes = 0;
				var seconds = 0;
				if ( ! /'/.test(str) )
					minutes = 0;
				if ( ! /''/.test(str) || ! /\"/.test(str) )
					seconds = 0;

				var deg = str.split ( /°/ );
				if ( deg [0] ) {
					degrees = parseFloat ( deg[0] );
					str = deg[1];
				} 

				if ( str ) {
					var x = str.split(/[^']*'[^']*/)
					if (x[0]) {
						minutes = parseFloat ( x[0] );
						str = x[1]
						if(str) {
							if (/''/.test(str)) {
								x = str.split("''")
								if( x[0] ) {
									seconds = parseFloat ( x[0] )
								}
								
							} else if ( /\"/.test(str)) {
								x = str.split("\"")
								if( x[0] ) {
									seconds = parseFloat ( x[0] )
								}
							} else {
								seconds = parseFloat ( str );
							}
						}
					} else if ( str ) {
						minutes = parseFloat ( str );
					}
				}
				return number ( degrees, minutes, seconds );
			};

			var setValues = function ( lat, lng ) {
				if ( ! ( lat && lng ) ) {
					//alert("error")
					return false;
				}
				lat = decimal ( lat )
				lng = decimal ( lng )
				if ( ! lat || ! lng ) {
					//alert ( "error" );
					return false;
				}
				//console . log ( "case 1: " + lat + " aaaa " + lng );
				$latInput.val(lat);
				$lngInput.val(lng);
				return true;
			};
			
			if ( /^N/.test ( val ) ) {
				//N 50°5.02407', E 13°51.96283'
				val = val.replace(/N\s*/, "" );
				val = val.replace(/\s*E\s*/, "" );
				var s = val . split ( "," )
				setValues ( s[0], s[1] );
			}
			else if ( /^\d+[.]?°/.test ( val ) ) {
				//50°5'1.444"N, 13°51'57.770"E
				//49°57'43.160"N, 14°22'59.901"E
				val = val.replace ("N", "");
				val = val.replace ("E", "");
				var s = val . split ( "," )
				setValues ( s[0], s[1] );

			}
			else if ( /^\d+[.]?\d*N,/.test ( val ) ) {
				//50.0837344N, 13.8660472E
				val = val.replace ("N", "");
				val = val.replace ("E", "");
				var s = val . split ( "," )
				setValues ( s[0], s[1] );

			}
			else if ( /^\d+[.]?\d*N\s/.test ( val ) ) {
				//50.0837344N 13.8660472E
				val = val.replace ("N", "");
				val = val.replace ("E", "");
				var s = val . split ( " " )
				setValues ( s[0], s[1] );
			}
			else if ( /^\d+[.]*\d*,\d+[.]*\d*/.test ( val ) ) {
				//console . log ( "two numbers divided by comma" )
				val = val.split(",");
				if(val[0] && val[1]) {
					$latInput.val(val[0]);
					$lngInput.val(val[1]);
				}
			}
			else {
				//console.log("number")
			}
		});

		if($(document).find('.pubForm').find('input[type=checkbox].switch').length) {
			$(document).find('.pubForm').find('input[type=checkbox].switch').bootstrapSwitch();
		}

		$(".pubForm-location").click ( function (e) {
			e . preventDefault ();
			if (navigator.geolocation) {
		        navigator.geolocation.getCurrentPosition(function ( position ) {
					var $span = $("span#alternatePubForm-accuracy")
					$span.removeClass()
					$span.addClass("text text-success")
					$span.empty();
					$span.html("Přesnost: " + position.coords.accuracy + "m." );
					if (position.coords.accuracy > 100) {
						$span.html( $span.html() + " Pozice je spíše nepřesná, zkuste použít satelity GPS nebo bezdrátové sítě." )
					}
					$latInput.val(position.coords.latitude).change();
					$lngInput.val(position.coords.longitude).change();

		        }, function ( error ) {
					var $span = $("span#alternatePubForm-accuracy")
					$span.removeClass()
					$span.addClass("text text-danger")
					$span.empty();
					$span.html("Nepodařilo se zjistit vaší polohu.")
		        });
		    } else { 
				var $span = $("span#alternatePubForm-accuracy")
				$span.removeClass()
				$span.addClass("text text-danger")
				$span.empty();
				$span.html("Váš prohlížeč nepodporuje získání polohy.")
		    }	
		});

	};

	$.nette.ext({
	  load: pubForm,
	  //init: pubForm
	});

	//pubForm();


	var beerForm = function () {
		var capitalize = function (s){
		    return s.toLowerCase().replace( /(?:^|\s)\S/g, function(a){ return a.toUpperCase(); } );
		};

		var enabled = true

		$(".beerForm").each( function (i) {
			var $that = $(this);
			var $txt = $("#beerForm-name");
			var prevTxtVal = $txt.val();
			var $deg = $("#beerForm-degree");
			var $link = $("#beerForm-link");
			var $reload = $("#beerForm-link").parent().find('a.input-group-addon');
			var $select = null;
			if(!($select = $("#beerForm-select-helper")).length){
				$select = $("<select id='beerForm-select-helper' name='not_important'></select>")
								.insertAfter($link)
								.addClass('form-control')
			}
			$select.hide()
				   .unbind();

			$select.change(function(e){
				e.preventDefault();
				$link.val($(this).val());
				$txt.val($(this).find("option:selected").data('beer-name')).change();
				var text = $(this).find("option:selected").text();
				text = (new RegExp("([0-9]+)°")).exec(text);
				if ( text && text[1] ) {
					$deg.val( text[1] ).change();
					prevTxtVal = text[1];
				} else {
					$deg.val(null).change();
					prevTxtVal = null;
				}
				$(this).hide();
				$(this).selectpicker('hide');
				$link.show();
			});

			//var $slc = $("#pubForm-type");
			
			// whispering

			var $enb = $("#beerForm-enable")
			var handler = function ( e ) {
				
				if ( !enabled )
					return;

				$txt.val( capitalize ( $txt.val () ) );
			};

			handler(null);
			$txt.unbind()
			$txt.keyup ( handler );
			$txt.change ( handler );

			$enb.click(function (e) {
				$(this).html( enabled ? 'Povolit našeptávání' : 'Zakázat našeptávání' )
				enabled = enabled ? false : true;
				if (enabled) {
					handler (null);
				}
				e.preventDefault();
			});

			// beer urls

			var handler2 = function (e) {

				if(! $that.data('search-url')) {
					return;
				}
				var search_url = $that.data('search-url') + "&by=" + $txt.val();

				$.nette.ajax ({
					url: search_url,
				},
				$(this),
				e
				).success(function(payload, status, jqXHR, settings) {
					$link.hide();
					$select.empty();
					for( var i = 0; i < payload.pubs.length; i ++ ){
						$opt = $("<option></option>");
						$opt.attr("value", payload.pubs[i].link)
						if ( i == 0 ) $opt.attr('selected', true );
						$opt.data('beer-name', payload.pubs[i].name);
						var name = payload.pubs[i].source + ': ' + payload.pubs[i].name
						if(payload.pubs[i].degree) {
							name += ' ' + payload.pubs[i].degree + '°';
						}
						$opt.text(name);
						$select.append($opt);
					}
					//$select.selectpicker();
					$select.selectpicker('show');
					$select.show();

					$select.next('div').find('li.selected').click(function(e){
						// click on default will insert the link
						$select.trigger('change');
					});

				}).error(function(jqXHR, status, error, settings){
					alert(error)
				});
				return false;
			}
			$txt.change ( function(e) {
				if(prevTxtVal.trim() == '') {
					handler2(e) 
				}
			});
			$reload.unbind() // fix nette load
			$reload.click( function(e) {
				e.stopPropagation();
				handler2(e);
			});
		});
	};

	$.nette.ext({
	  load: beerForm,
	  //init: beerForm
	});

	//beerForm();

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
		if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
		    $('.selectpicker').selectpicker('mobile');
		} else {
			$(".selectpicker").selectpicker();
		}
	      //$.nette.load();
	  }
	});



})(jQuery);


