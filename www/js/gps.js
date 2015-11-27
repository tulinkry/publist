/**
 * Unobtrusive handler for GpsPicker
 *
 * @author Vojtěch Dobeš
 * @license New BSD
 *
 * @dependency jQuery
 * @dependency Google Maps API v3 || Nokia Maps v1.2 || Mapy.cz API v4
 * @dependency netteForms.js (optional)
 */

(function(window, undefined) {


var google = window.google;
var nokia = window.nokia;
var SMap = window.SMap;
var $ = window.jQuery;
var Nette = window.Nette;

if (!$) {
	console.error("'nette-forms-gpspicker' requires jQuery.");
	return;
}



var shapes = {
	point: (function () {
		var PointShape = function ($el, $inputs) {
			this._el = $el;
			this._latInput = $inputs.filter('[id$=lat]');
			this._lngInput = $inputs.filter('[id$=lng]');
			this._address = $inputs.filter('[id$=address]');
			this._location = $inputs.filter('[id$=location]');
		};
		PointShape.prototype.fill = function (lat, lng, loc, adr) {
			this._latInput.val(lat);
			this._lngInput.val(lng);
			this._address.val(adr);
			this._location.val(loc);
			this._el.trigger('change.gpspicker', [{
				lat: lat,
				lng: lng,
				address: adr,
				location: loc
			}]);
			this._el.trigger('own.gpspicker', [{
				lat: lat,
				lng: lng,
				address: adr,
				location: loc
			}]);
		};
		PointShape.prototype.setValue = function (lat, lng) {
			lat = lat * 1;
			lng = lng * 1;
			this.fill(lat, lng, this._address, this._location);
			this._onSetValue(lat, lng);
		};
		PointShape.prototype.getValue = function () {
			return {
				lat: this._latInput.val(),
				lng: this._lngInput.val(),
				address: this._address.val(),
				location: this._location.val()
			};
		};
		return PointShape;
	})()
};

var drivers = {
	google: {
		isSupported: !!google,
		createMap: function ($container, options) {
			return new google.maps.Map($container[0], {
				mapTypeId: google.maps.MapTypeId[options.type] || google.maps.MapTypeId.ROADMAP
			});
		},
		search: function ($search) {
			var options = { types: [ 
									'address',
									//'restaurant',
									//'bar',
									//'casino'
									],
							componentRestrictions: { 'country': 'cz' } };
			return new google.maps.places.Autocomplete($search.get(0), options || {} );
		},
		shapes: {
			point: function (shape, options) {
				var val = shape.getValue();
				var position = new google.maps.LatLng(val.lat * 1, val.lng * 1);
				shape.map.setCenter(position);
				shape.map.setZoom(options.zoom);

				shape.marker = new google.maps.Marker({
					position: position,
					map: shape.map,
					draggable: !options.disabled
				});
				shape._onSetValue = function (lat, lng) {
					this.marker.setPosition(new google.maps.LatLng(lat, lng));
				};

				var geoloc = function ( shape, lat, lng ) {
					/** reverse geolocation */
					var geocoder = new google.maps.Geocoder();
					var latlng = new google.maps.LatLng(lat, lng)
					geocoder.geocode({'latLng': latlng}, function(results, status) {
						var address = null
						var location = null
					    if (status == google.maps.GeocoderStatus.OK) {
						  if(results[0]) {
					        var parts2 = options.parseAddressComponents ( results );
					        address = results[0].formatted_address
					        location = parts2.length ? parts2.join(", ") : address;
					      } else {
					        alert('No results found');
					      }
					    } else {
					      alert('Geocoder failed due to: ' + status);
					    }


						shape.fill(latlng.lat(), latlng.lng(), location, address);
					 });
				};

				if (!options.disabled) {
					google.maps.event.addListener(shape.marker, 'mouseup', function (e) {
						shape.fill(e.latLng.lat(), e.latLng.lng(), null, null);
					});

					var timeout;
					google.maps.event.addListener(shape.map, 'click', function (e) {
						timeout = setTimeout(function () {

							var latlng = new google.maps.LatLng(e.latLng.lat(), e.latLng.lng())
							
							geoloc(shape,e.latLng.lat(),e.latLng.lng())

							shape.marker.setPosition(e.latLng);
							shape.marker.setMap(shape.map);
						}, 200);
					});
					google.maps.event.addListener(shape.map, 'dblclick', function (e) {
						if (timeout) {
							clearTimeout(timeout);
							timeout = null;
						}
					});

					if (options.search) {
						google.maps.event.addListener(options.search, 'place_changed', function () {
							var place = options.search.getPlace();
							if (!place.geometry) return;

							var location = place.geometry.location;
							if (place.geometry.viewport) {
								shape.map.fitBounds(place.geometry.viewport);
							} else {
								shape.map.setCenter(location);
								shape.map.setZoom(17);
							}
							shape.marker.setPosition(location);
							shape.fill(location.lat(), location.lng(), null, null);
						});
					}

					if (options.helpers) {
						$bnt = $(".try-geoloc")
						
						var clickHandler = function (e){
						    if (navigator.geolocation) {
						        navigator.geolocation.getCurrentPosition(function ( position ) {
									
									var latlng = new google.maps.LatLng(position.coords.latitude,
				                                       				    position.coords.longitude);

									geoloc(shape, position.coords.latitude, position.coords.longitude)
					
									shape.marker.setPosition(latlng);
									shape.marker.setMap(shape.map);
									shape.marker.map.setCenter(latlng);			
						        }, function ( error ) {
									alert( error.code );
						        });
						    } else { 
						        alert( "Geolocation is not supported by this browser." );
						    }
						};



						$bnt.click(clickHandler);
						//$bnt . appendTo (shape._el);
					}
				}
			}
		}
	},
	nokia: {
		isSupported: !!nokia,
		createMap: function ($container, options) {
			if (options.type === 'ROADMAP') {
				options.type = 'NORMAL';
			}
			return new nokia.maps.map.Display($container[0], {
				baseMapType: nokia.maps.map.Display[options.type],
				components: [
					new nokia.maps.map.component.Behavior(),
					new nokia.maps.map.component.ZoomBar(),
					new nokia.maps.map.component.Overview(),
					new nokia.maps.map.component.TypeSelector(),
					new nokia.maps.map.component.ScaleBar()
				]
			});
		},
		shapes: {
			point: function (shape, options) {
				var val = shape.getValue();
				var coordinate = new nokia.maps.geo.Coordinate(val.lat * 1, val.lng * 1);

				shape.marker = new nokia.maps.map.StandardMarker(coordinate, {
					draggable: !options.disabled
				});
				shape.map.objects.add(shape.marker);
				shape._onSetValue = function (lat, lng) {
					this.marker.set('coordinate', new nokia.maps.geo.Coordinate(lat, lng));
				};

				shape.map.setCenter(coordinate);
				shape.map.setZoomLevel(options.zoom);

				if (!options.disabled) {
					shape.marker.addListener('dragend', function () {
						shape.fill(shape.coordinate.latitude, shape.coordinate.longitude, null, null);
					}, false);

					var timeout;
					shape.map.addListener('click', function (e) {
						if (timeout) {
							clearTimeout(timeout);
						}
						timeout = setTimeout(function () {
							var coordinate = shape.map.pixelToGeo(e.displayX, e.displayY);
							shape.marker.set('coordinate', coordinate);
							shape.fill(coordinate.latitude, coordinate.longitude, null, null);
						}, 200);
					});
					shape.map.addListener('mapviewchangestart', function (e) {
						if (timeout) {
							clearTimeout(timeout);
							timeout = null;
						}
					});
				}
			}
		}
	},
	seznam: {
		isSupported: !!SMap,
		createMap: function ($container, options) {
			var map = new SMap(
				$container.get(0),
				SMap.Coords.fromWGS84(14.41, 50.08),
				10
			);
			if (options.type === 'ROADMAP') {
				options.type = 'BASE';
			} else if (options.type === 'SATELLITE') {
				options.type = 'OPHOTO';
			}
			map.addDefaultLayer(SMap['DEF_'+ options.type]).enable();
			map.addDefaultControls();
			return map;
		},
		shapes: {
			point: function (shape, options) {
				var val = shape.getValue();
				var position = SMap.Coords.fromWGS84(val.lng * 1, val.lat * 1);

				var layer = new SMap.Layer.Marker();
				shape.map.addLayer(layer);
				layer.enable();

				shape.marker = new SMap.Marker(position, '', {});
				if (!options.disabled) {
					var mouse = new SMap.Control.Mouse(SMap.MOUSE_PAN | SMap.MOUSE_WHEEL | SMap.MOUSE_ZOOM);
					shape.map.addControl(mouse);
					shape.marker.decorate(SMap.Marker.Feature.Draggable);
				}
				layer.addMarker(shape.marker);
				shape._onSetValue = function (lat, lng) {
					this.marker.setCoords(SMap.Coords.fromWGS84(lng, lat));
				};

				shape.map.setCenterZoom(position, options.zoom);

				if (!options.disabled) {
					var signals = shape.map.getSignals();
					signals.addListener(window, 'marker-drag-stop', function (e) {
						var coords = e.target.getCoords().toWGS84();
						shape.fill(coords[1], coords[0], null, null);
					});

					var timeout;
					signals.addListener(shape.map, 'map-click', function (e) {
						if (timeout) {
							clearTimeout(timeout);
						}
						timeout = setTimeout(function () {
							var coords = SMap.Coords.fromEvent(e.data.event, shape.map);
							shape.marker.setCoords(coords);
							coords = coords.toWGS84();
							shape.fill(coords[1], coords[0], null, null);
						}, 200);
					});
					signals.addListener(shape.map, 'zoom-start', function (e) {
						if (timeout) {
							clearTimeout(timeout);
							timeout = null;
						}
					});
				}
			}
		}
	}
};
drivers.openstreetmap = {
	isSupported: !!google,
	createMap: function ($container, options) {
		var map = new google.maps.Map($container[0], {
			mapTypeId: 'OSM',
			mapTypeControlOptions: {
				mapTypeIds: []
			}
		});
		map.mapTypes.set('OSM', new google.maps.ImageMapType({
			getTileUrl: function(coord, zoom) {
				return 'http://tile.openstreetmap.org/' + zoom + '/' + coord.x + '/' + coord.y + '.png';
			},
			tileSize: new google.maps.Size(256, 256),
			name: 'OpenStreetMap',
			maxZoom: 18
		}));
		return map;
	},
	shapes: drivers.google.shapes
};

var GpsPicker = function () {
	var that = this;
	var map, shape;

	var parseDataAttribute = function (el) {
		return eval('[{' + (el.getAttribute('data-nette-gpspicker') || '') + '}]')[0];
	};

	this.parseAddressComponents = function ( results ) {
		if (! results.length)
			return [];
		parts = {};

		for ( var i = 0; i < results[0].address_components.length; i ++ )
		{
			if ( results[0].address_components[i].types.indexOf('locality') != -1 )
				parts [ 'locality' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('neighborhood') != -1 )
				parts [ 'neighborhood' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('country') != -1 )
				parts [ 'country' ] = results[0].address_components[i].long_name


			// sublocality
			if ( results[0].address_components[i].types.indexOf('sublocality') != -1 )
				parts [ 'sublocality' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('sublocality_level_1') != -1 )
				parts [ 'sublocality_level_1' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('sublocality_level_2') != -1 )
				parts [ 'sublocality_level_2' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('sublocality_level_3') != -1 )
				parts [ 'sublocality_level_3' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('sublocality_level_4') != -1 )
				parts [ 'sublocality_level_4' ] = results[0].address_components[i].long_name
			if ( results[0].address_components[i].types.indexOf('sublocality_level_5') != -1 )
				parts [ 'sublocality_level_5' ] = results[0].address_components[i].long_name
		}	
		parts2 = [];

		sublocality = parts['sublocality'] || 
					  parts['sublocality_level_1'] ||
					  parts['sublocality_level_2'] ||
					  parts['sublocality_level_3'] ||
					  parts['sublocality_level_4'] ||
					  parts['sublocality_level_5'];
		sublocality_name = "";

		if(parts['neighborhood']) parts2 . push( parts ['neighborhood'] );

		for( var i = 5; i > 0; i -- )
			if(parts['sublocality_level_' + i]) 
				sublocality_name = parts['sublocality_level_' + i];
		if(sublocality_name != "") parts2 . push(sublocality_name);

		if(parts['sublocality'] && sublocality_name == "") parts2 . push( parts ['sublocality'] );

		sublocality_name = sublocality_name == "" && parts['sublocality'] ? parts['sublocality'] : sublocality_name;

		if(parts['locality'] && ! (new RegExp(parts['locality'], "g")).test(sublocality_name) ) parts2 . push( parts['locality'] )
		if(parts['country']) parts2 . push( parts['country'] )

		return parts2;
	};

	$.fn.gpspicker = function (options) {
		return this.each(function () {
			that.initialize(this, options);
		});
	};

	this.map = function () {
		return that.map;
	}

	this.shape = function () {
		return that.shape
	}

	this.load = function () {
		return $('[data-nette-gpspicker]').gpspicker();
	};

	this.initialize = function (el, options) {
		var $el = $(el), gpspicker;
		if (gpspicker = $el.data('gpspicker')) return gpspicker;

		var options = $.extend(parseDataAttribute(el), options || {});
		options = $.extend( { 'parseAddressComponents': this.parseAddressComponents }, options );

		var x = options.size.x;
		var y = options.size.y;

		var $mapContainer = $('<div>', {
			width: typeof x == 'number' ? x + 'px' : x,
			height: typeof y == 'number' ? y + 'px' : y,
			position: 'relative'
		}).prependTo($el);
		var $inputs = $el.find('input:not([id$=search])');
		if (!options.manualInput) {
			$inputs.hide();
		} else {
			$inputs.on('change.gpspicker input.gpspicker', function () {
				var args = [];
				$inputs.each(function () {
					args.push($(this).val());
				});
				$el.data('gpspicker').setValue.apply($el.data('gpspicker'), args);
			});
		}
		$el.find('label').hide();
		
		if(options.helpers)
		{

			var fnc = function (e) {
				var $el = $("#" + options.helperId)
				var info = $el.find("span.info").length ? $el.find("span.info")[0].outerHTML : "<span class='info'></span>";

				if ( ! $el.find('table').length ) {
					$table = $("<table class='table table-striped'></table>");

					//$table . append ( "<tr><th>Latitude</th><td colspan='2' id='gpspicker-lat-helper'></td></tr>" );
					//$table . append ( "<tr><th>Longitude</th><td colspan='2' id='gpspicker-lng-helper'></td></tr>" );
					$table . append ( 
						"<tr>" +
							"<th>Adresa</th>" +
							"<td>" +
								"<input type='text' id='gpspicker-address-helper' value='' class='form-control' />" +
								info +
							"</td>" +
							"<td>" +
								"<a id='gps-address' class='btn btn-primary'>Najít</a>" +
							"</td>" +
						"</tr>" );
					$table . append ("<tr><th>Lokalita</th><td colspan='2' id='gpspicker-location-helper'></td></tr>" );
					$table . append ("<tr><th>Automatická detekce</th><td colspan='2'><a class='try-geoloc btn btn-primary'>Zjistit aktuální polohu</a></td></tr>")
					$el . append ( $table );
				}
				$("#gpspicker-lat-helper").html ($( ".gpspicker-lat" ) . val ());
				$("#gpspicker-lng-helper").html($( ".gpspicker-lng" ) . val ());
				$("#gpspicker-address-helper").val($( ".gpspicker-address" ) . val ())
				$("#gpspicker-location-helper").html($( ".gpspicker-location" ) . val ())
			};
			fnc ();

			$(document).on( "own.gpspicker", fnc );
		}

		var driver = drivers[options.driver];
		if (!driver.isSupported) {
			console.error("Driver '" + options.driver + "' misses appropriate API SDK.");
			return $el;
		}
		that.map = driver.createMap($mapContainer, options);

		if (options.search && driver.search) {
			var $search = $el.find('[id$=search]');
			if ($search.length) {
				$search.show();
			} else {
				$search = $('<input>', {
					type: 'text'
				}).prependTo($el);
			}
			options.search = driver.search($search);
		}

		that.shape = new shapes[options.shape]($el, $inputs);
		that.shape.map = that.map;
		driver.shapes[options.shape](that.shape, options);




		var changeFn = function (e) {
			var errorHandler = function ( $el, msg, errorlevel, type ) {
				if (!errorlevel)
					errorlevel = 'success';
				if(!type)
					type = "label";
	      		var $span = $el.parent().find("span.info");
	      		$span2 = $("<p></p>")
	      		$span2.html( msg );
	      		$span2.addClass( type + " " + type + "-" + errorlevel );
	      		$span.append($span2)
			};

			var $el2 = $(this).parent().parent().find('td input');
			$el2.parent().find('span.info').empty();

			var geocoder = new google.maps.Geocoder();
			geocoder.geocode({'address': $el2.val()}, function(results, status) {
				var address = null
				var location = null
			    if (status == google.maps.GeocoderStatus.OK) {
				  if (results.length) {

				      if (results[0].geometry) {
				      	if (results[0].geometry.location_type == 'APPROXIMATE') {
				      		errorHandler ( $el2, "Lokace je velmi nepřesná, pokuste se zadat přesnější adresu.", 'warning' );
				      	} else if (results[0].geometry.location_type == 'ROOFTOP') {
				      		errorHandler ( $el2, "Nalezena!", 'success' );
				      	}

				        address = results[0].formatted_address
						var latlng = results[0].geometry.location

						var parts2 = options.parseAddressComponents ( results );

						location = parts2.length ? parts2.join(", ") : address;
						if(!parts2.length){
				      		errorHandler ( $el2, "Lokalita nebyla nastavena (výchozí nastavení je stejné jako adresa), zkuste prosím zadat přesnější adresu.", 'warning', "text" );
						}

						that.shape.fill(latlng.lat(), latlng.lng(), location, address);
						that.shape.marker.setPosition(latlng);
						that.shape.marker.setMap(that.shape.map);
						that.shape.marker.map.setCenter(latlng);									
				      } else {
			      		errorHandler ( $el2, "Zadaná adresa neexistuje!", 'danger' );			      	
				      }
				    } else {
			      		errorHandler ( $el2, "Zadaná adresa neexistuje!", 'danger' );			      	
				    }
				} else {
		      		errorHandler ( $el2, "Zadaná adresa neexistuje!", 'danger' );			      	

				}

			 });
			e.preventDefault ();
		};

		
		$( "#gps-address" ) . click ( changeFn );
		
		return $el.data('gpspicker', that.shape);
	};

	$(function () {
		// Twitter Bootstrap
		var rules = [
			'[data-nette-gpspicker] img { max-width: none; }'
		];
		try {

			var stylesheet = window.document.styleSheets[0];
			var method = stylesheet.cssRules ? 'insertRule' : 'addRule';
			for (var i = 0; i < rules.length; i++) {
				stylesheet[method].call(stylesheet,	 rules[i], 0);
			}
		} catch ( e ){}

		if (Nette) {
			Nette.validators.maxLat = function (elem, arg, value) {
				return value <= arg;
			};
			Nette.validators.maxLng = function (elem, arg, value) {
				return value <= arg;
			};
			Nette.validators.minLat = function (elem, arg, value) {
				return value >= arg;
			};
			Nette.validators.minLng = function (elem, arg, value) {
				return value >= arg;
			};
		}

		that.load();
	});
};

var GpsPicker = window.NetteGpsPicker = window.NetteGpsPicker || new GpsPicker();

})(window);
