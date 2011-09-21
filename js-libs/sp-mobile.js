/**
 *  
 *  SP - Skip's Picks javascript library
 *
 */

/**
 * startup
 */
(function () {

  // document.domain = "skipspicks.net";
  var subs = location.hostname.split(".");
  while (subs.length > 2)
      subs.shift()
  document.domain = subs.join(".");

  // global
  $('div').live('pagebeforeshow', function() {
    $('div[data-role="footer"]').html($('div#static-footer').html());
  });

  // home page
  $('div#home').live('pagebeforeshow', function() {
    // only put add link if logged in
    if (SP.readCookie('user-name')) {
      if ($('#homeLinks[data-inc!="add"]')) {
        $('#homeLinks[data-inc!="add"]').append('<li id="homeListAdd"><a href="add.php?">Add Location</a></li>').listview('refresh');
        $('#homeLinks').attr('data-inc', 'add');
      }
    } else {
      $('#homeListAdd').remove();
      $('#homeLinks').attr('data-inc', '');
    }
  });

  // add page
  $('div#add').live('pageshow', function() {
    SP.setAddPage();
  });
  $('div#add').live('pagehide', function() {
    $('div#add').remove();
  });

  // nearby
  $('div#nearby').live('pageshow', function() {
    SP.getNearby();
  });
  $('div#nearby').live('pagehide', function() {
    // $('div#nearby').remove();
  });

  // detail
  $('div#detail').live('pageshow', function() { // may be pageshow if not works...
    var regexp = /id=(\d+)/; // i think this requires pageshow so hash is changed
    var match = regexp.exec(window.location.hash);
    var id = match[1];
    var regexp = /type=(\w+)/;
    var match = regexp.exec(window.location.hash);
    var type = (match && match.length > 0) ? match[1] : null;
    SP.getDetail(id, type);
  });
  $('div#detail').live('pagehide', function() {
    // $('div#detail').page("destroy");
    // $('div#detail').remove();
  });

  // favorites
  $('#favs').live('pageshow', function() {
    SP.getFavorites();
  });

  // search filter popup dialog
  $('div#search-filter').live('pagehide', function() {
    $('div#search-filter').remove();
  });

  // for setting location
  $('div#loc-dialog').live('pageshow', function() {
    $('#loc-form').submit(function(e) {
      e.preventDefault();
      var loc = {};
      loc.address = $('input:[name="address"]', $(this)).val();
      loc.city = $('input:[name="city"]', $(this)).val();
      loc.state = $('input:[name="state"]', $(this)).val();
      loc.zip = $('input:[name="postal_code"]', $(this)).val();
      // dynamically load google api
      SP.loadGMapApi.callback = function() {
        SP.reverseGeoCode(loc, function(point) {
          if (point) {
            var geo = point.geometry.location;
            SP.log(geo, 3);
            var geopos = { 'coords': { 'latitude': geo.lat(), 'longitude': geo.lng() } };
            SP.log(geopos, 3);
            SP.createCookie('user-location', JSON.stringify(geopos), 30);
            if (/#&/.test(window.location.hash)) {
              window.location.href = "/";
            } else {
              var to = window.location.hash.substr(1)
              $.mobile.changePage(to, 'slide', true, false);
            }
          }
        })
      };
      SP.loadGMapApi.loadjsapi();
      return false;
    });
  });

  // testing area
  $(document).ready(function() {
    // SP.eraseCookie('user-location');
    // SP.postTwitterStatus('testing!');
    // console.log(SP.getGKey());
    // SP.removeFavoriteFromDB("2004");
  });

}());

if (!this.SP) {
    this.SP = {};
}

(function () {

    // class variables
    var jsapiLoaded = false;
    var logLevel = 2;

    // lawnchair dbs
    var locationDB; 
    var favoriteDB;
    var geopos = {};

    // our SP object
    SP = {

        // page-functionality ------------------------------------------------------------

        post: function(url, method, data, callback) {
            $.ajax({
                url: url,
                type: method || "GET",
                cache: false,
                data: data,
                success: function(results){
                  SP.log('$ajax.success', 3);
                  callback(results);
                },
                complete: function(request, status){
                  if (status != 'success') {
                    SP.log("ajax status:" + status, 3);
                    SP.log(request, 3);
                  }
                }
            });
        },

        /**
         * callback takes a single param: obj
         * f: {qt, id, details, sort, ...} // filters
         */
        locationRestRequest: function(f, callback) {
          SP.log('locationRestRequest()', 3);
          var url = "/rest/v1/locations/";

          // parse f object into url
          url += (f.id) ? f.id : "";
          url += "?qt=" + ((f.qt) ? f.qt : '');
          url += "&sort=" + ((f.sort) ? f.sort : '');
          url += "&details=" + ((f.details) ? f.details : '');
          url += "&rating=" + ((f.rating) ? f.rating : '');
          url += "&price=" + ((f.price) ? f.price : '');
          url += "&lat=" + ((f.latitude) ? f.latitude : '');
          url += "&lng=" + ((f.longitude) ? f.longitude : '');

          SP.log(url, 3);

          SP.post(url, "GET", null, function(response) {
            // add all returned locs to client db; BEER: simplify to { id, type }??
            var locs = response.locations;
            for (var i = 0; i < locs.length; i++) {
              SP.addLocationToDB(locs[i]);
            }
            callback(locs);;
          });
        },

        preProcessSubmitForm: function(frm) {
          // $('div[id^="search.php"]').remove(); // named by jquery!
        },

        preProcessAddForm: function(frm) {
          $('input:disabled').removeAttr('disabled');
          $('input:hidden').removeAttr('disabled');

          var url = frm.attr('action'); // already has id if exists
          console.log(url);

          SP.log('retrieving lat lng', 2);
          var loc = {};
          loc.address = $('input:[name="address"]', frm).val();
          loc.city = $('input:[name="city"]', frm).val();
          loc.state = $('input:[name="state"]', frm).val();
          loc.zip = $('input:[name="postal_code"]', frm).val();
          // dynamically load google api
          SP.loadGMapApi.callback = function() {
            SP.reverseGeoCode(loc, function(point) {
              if (point) {
                var geo = point.geometry.location;
                SP.log(geo, 2);
                $('input:[name="lat"]', frm).val(geo.lat());
                $('input:[name="lng"]', frm).val(geo.lng());
                $.mobile.changePage({
                  url: url,
                  type: "POST", 
                  data: frm.serialize()
                }, 'pop', false, false); 
              } else {
                // if no results, get from navigator
                SP.log('reverting to device location', 2);
                SP.withGeoLocation(function(geoposition) {
                  if (geoposition) {
                    var geo = geoposition.coords;
                    $('input:[name="lat"]', frm).val(geo.latitude);
                    $('input:[name="lng"]', frm).val(geo.longitude);
                    $.mobile.changePage({
                      url: url,
                      type: "POST", 
                      data: frm.serialize()
                    }, 'pop', false, false); 
                  }
                });
              }
            });
          };
          SP.loadGMapApi.loadjsapi();
        },

        /**
         * add.php
         * for filling in form fields w/ location information
         */
        setAddPage: function() {
          SP.log('calling setAddPage', 3);
          if (typeof id != "undefined" && /id=\d+/.test(window.location.hash)) {
            SP.getLocationFromDB(id, function(obj) {
              for (var field in obj) {
                var name = "#add" + field.charAt(0).toUpperCase() + field.slice(1);
                var inpt = $(name);
                if (inpt && obj[field]) {
                  inpt.val(obj[field]);
                  inpt.attr('disabled', 'disabled');
                }
              }
              $('div#add').page();
            });
          }
          $('#addUser').val(SP.readCookie('user-name'));
          $('#addUserId').val(SP.readCookie('user-id'));
        },

        /**
         * get list of favorites
         */
        getFavorites: function() {
          SP.log('getFavorites()', 3);
          SP.getAllFavoritesFromDB(function(locs) {
            SP.log(locs, 3);
      
            var locations = [];
            for ( var i = 0; i < locs.length; i++ ) {
              SP.getLocationFromDB(locs[i].key, function(loc) {
                locations.push(loc);
              });
            }
            $('ul#favorites').html(tmpl("list_tmpl", locations)); // .page();
            $('ul#favorites').listview('refresh');
            $('#favorites').page();
          });
        },

        /**
         * run a search, called from search page
         * filter: object of search indicators
         */
        doSearch: function(filter) {
          SP.log('doSearch()', 3);
          SP.log(filter, 3);
          SP.withGeoLocation(function(geoposition) {
            filter.latitude = geoposition.coords.latitude;
            filter.longitude = geoposition.coords.longitude;
            SP.locationRestRequest(filter, function(locations) {
              $('ul#searchresults').html(tmpl("list_tmpl", locations)).page();
              $('ul#searchresults').listview('refresh');
            });
          });
        },


        // user functionality -------------------------------------------------------
        getUser: function(user, password, encode, callback) {
          SP.log('getUser: ' + user + '/' + password, 3);
          var url = "/rest/v1/users/" + user + "?password=" + password;
          if (geopos.coords)
            url += "&lat=" + geopos.coords.latitude + "&lng=" + geopos.coords.longitude;
          if (!encode) url += "&enc=false";
          SP.log('url: ' + url, 3);
          SP.post(url, 'GET', null, function(user) {
            if (user && user.status == 'okay') {
              SP.createCookie('user-name', user.user_name, 30);
              SP.createCookie('user-password', user.password, 30);
              SP.createCookie('user-id', user.user_id, 30);
              callback(user);
            } else {
              alert('Please check your user name and password and try again.');
            }
          });
        },
        createUser: function(frm, callback) {
          SP.log('createUser', 3);
          var url = "/rest/v1/users/" + frm.user_name.value + "?password=" + frm.password.value + "&email=" + frm.email.value;
          SP.log(url, 3);
          SP.post(url, 'POST', null, function(user) {
            if (user && user.status == 'okay') {
              SP.log('creating: ', 3);
              SP.log(user);
              SP.createCookie('user-name', user.user_name, 30);
              SP.createCookie('user-password', user.password, 30);
              SP.createCookie('user-id', user.user_id, 30);
              callback(user);
            } else {
              alert('There was a problem adding the user; that username might already exist.');
            }
          });
        },
        logOut: function(callback) {
          SP.log('logOut()', 3);
          SP.eraseCookie('user-name');
          SP.eraseCookie('user-password');
          SP.eraseCookie('user-id');
          callback();
        },
        
        
        // json functionality -------------------------------------------------------
        
        /**
         * map the yahoo results to sp db fields
         */
        convertYahooLocationToSPLocation: function(yahooLoc) {
          var spLoc = {};
          spLoc.yahoo_id = yahooLoc.id;
          spLoc.name = yahooLoc.Title;
          spLoc.address = yahooLoc.Address;
          spLoc.city = yahooLoc.City;
          spLoc.state = yahooLoc.State;
          spLoc.postal_code = "";
          spLoc.phone = yahooLoc.Phone;
          spLoc.lat = yahooLoc.Latitude;
          spLoc.lng = yahooLoc.Longitude;
          spLoc.url = yahooLoc.BusinessClickUrl;
          spLoc.avg_rev_rating = yahooLoc.Rating.AverageRating;
          spLoc.distance = yahooLoc.Distance;
          spLoc.reviews = [{body: yahooLoc.Rating.LastReviewIntro}];
          spLoc.type = 'yahoo';
          return spLoc;
        },

        // json persistence; using Lawnchair as a back
        initLocationDB: function() {
          if (locationDB)
            return;
          SP.log('init locationDB', 2);
          locationDB = new Lawnchair({table: 'location', adaptor: 'dom'}, function(r) {
            SP.log("db init'd", 2);
          });
        },
        addLocationToDB: function(loc, callback) {
          SP.initLocationDB();
          loc.key = (loc.location_id) ? loc.location_id : loc.yahoo_id;
          SP.log('adding loc ' + loc.key + ' to db', 3);
          SP.log(loc, 3);
          if (callback) {
            locationDB.save(loc, callback);
          } else {
            locationDB.save(loc, function(r) {}); // Bug in lawnchair requires a callback or no key!!
          }
        },
        getLocationFromDB: function(key, callback) {
          SP.initLocationDB();
          SP.log('retrieving loc ' + key + ' from db', 3);
          locationDB.get(key, callback);
        },
        removeLocationFromDB: function(key) {
          SP.initLocationDB();
          SP.log('deleting loc ' + key + ' from db', 3);
          locationDB.remove(key);
        },
        
        initFavoriteDB: function() {
          if (favoriteDB)
            return;
          SP.log('init favoriteDB', 3);
          favoriteDB = new Lawnchair({table: 'favorites', adaptor: 'dom'}, function(r) {
            SP.log("favorite db init'd", 3);
          });
        },
        // assumes they already have keys; may need to re-architect
        addFavoriteToDB: function(loc, callback) {
          SP.initFavoriteDB();
          // only add keys for normalization
          var fav = {key: loc.key};
          SP.log('adding fav ' + fav.key + ' to favorites db', 3);
          SP.log(fav, 3);
          if (callback) {
            favoriteDB.save(fav, callback);
          } else {
            favoriteDB.save(fav, function(r) {}); // Bug in lawnchair requires a callback or no key!!
          }
        },
        getAllFavoritesFromDB: function(callback) {
          SP.initFavoriteDB();
          SP.log('retrieving locs from favorite db', 3);
          favoriteDB.all(callback);
        },
        getFavoriteFromDB: function(key, callback) {
          SP.initFavoriteDB();
          favoriteDB.get(key, callback);
        },
        removeFavoriteFromDB: function(key) {
          SP.initFavoriteDB();
          SP.log('deleting fav ' + key + ' from db', 2);
          favoriteDB.remove(key);
        },
        
        // geolocation functionality -------------------------------------------------------

        geocode: function(address, callback) {
          var geocoder = new google.maps.Geocoder();

          var latlng = new google.maps.LatLng(address.latitude, address.longitude);
          geocoder.geocode({'latLng': latlng}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
              if (results[0]) {
                SP.log(results, 3);
              }
            } else {
              alert("Geocoder failed due to: " + status);
            }
          });
        },

        /**
         * takes an object: {address, city, state}
         */
        reverseGeoCode: function(loc, callback) {
          var geocoder = new google.maps.Geocoder();
          // var address = loc.address + ',' + loc.city + ',' + loc.state + ',' + loc.zip;
          var address = loc.address + ',' + loc.city + ',' + loc.state;
          if (loc.zip)
            address += ',' + loc.zip;
          address = address.replace(/\s/g, "+");
          console.log(address);
          geocoder.geocode( { 'address': address }, function(results, status) {
            if (status != google.maps.GeocoderStatus.OK) {
              SP.log("Geocode was not successful for the following reason: " + status, 1);
            }
            callback(results[0]);
          });
        },

        /**
         * get current location.  asynchronous, pass a callback
         * callback receives position object
         *
         * for efficiency, should check last update, and, based on flag, only call if been past time
         * return boolean if has changed
         *
         */
        withGeoLocation: function(callback, onSameCallback) {
          try {
            var userLoc = JSON.parse(SP.readCookie('user-location'));
          } catch(err) {
            console.log(err);
          }
          if (userLoc && typeof(userLoc) == 'object') {
            SP.log('using cookie location', 3);
            geopos = userLoc;
          }
          if (geopos.coords) {
            SP.log('cached', 3);
            if (onSameCallback) {
              onSameCallback();
            } else {
              callback(geopos);
            }
            return;
          }
          if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(geoposition) {
              SP.log('new GEO!', 3);
              geopos = geoposition;
              callback(geopos);
            }, function(error) {
              SP.log("geo locating threw the error [" + error.code + "] " + error.message, 1);
              $.mobile.changePage('loc-dialog.html', 'pop', true, false);
            }, {
              maximumAge: 300000, // 5 minutes
              timeout: 5000, // 5 seconds
              enableHighAccuracy: true
            });
          } else if (typeof(Mojo) !="undefined" && typeof(Mojo.Service.Request) != "Mojo.Service.Request") {
            // webOs
            r = new Mojo.Service.Request('palm://com.palm.location', {
              method:"getCurrentPosition",            
              parameters: {accuracy: 1, maximumAge: 0, responseTime: 1}, 
              onSuccess: function(p) {            
                callback({
                  timestamp: p.timestamp, 
                    coords: {
                    latitude: p.latitude,
                    longitude: p.longitude,
                    heading: p.heading
                  }
                });
              },
              onFailure: function(e) { SP.log("error getting mojo geolocation: " + e, 1); }
            });
          } else {
            SP.log("browser does not support geo positioning", 2);
          }
        },

        // this does not seem to be working; BEER: must fix for mobile speed....
        loadGMapApi: {
          callback: null,
          loadjsapi: function() {
            SP.log('loadjsapi()', 2);
            // if (!jsapiLoaded) {
              SP.log('loading now', 2);
              var script = document.createElement("script");
              script.src = "http://www.google.com/jsapi?key=" + SP.getGKey() + "&callback=SP.loadGMapApi.loadMapAPI";
              // script.type = "text/javascript";
              document.getElementsByTagName("head")[0].appendChild(script);
              jsapiLoaded = true;
            // }
            // SP.log('already loaded', 2);
            // this.callback();
          },
          loadMapAPI: function() {
            console.log('loaded!');
            google.load('maps', '3', {"other_params": "sensor=true", "callback" : this.callback});
          }
        },

        getGKey: function() {
            var gnetkey = 'ABQIAAAAuRZkfXnOvWgSRQYXsrsfjBQBSCnPmKbx2s6PCP8pRShTn_b5OBQXc1NFLmbXp9uuJFBJ5l8y3HzFMA';
            var gcomkey = 'ABQIAAAAuRZkfXnOvWgSRQYXsrsfjBSKPUBGkfe_1QsR14S-5yUtlhJh1RSGSOd1TAIgZapEt_u6-U74FEgKJA';

          if (/skipspicks.com/.test(window.location.hostname))
            return gcomkey;
          return gnetkey;
        },

        /**
         * Twitter
         * for now calling from php post code, not from javascript
         * save for possible later use
         * support is in rest.php also for js
         */
        postTwitterStatus: function(status) {
          var url = '/php/rest.php?api=twitter&status=' + status;
          SP.post(url, 'GET', null, null);
        },


        // helpers ------------------------------------------------------------------------

        loadScript : function(sScriptSrc, oCallback) {
            var oHead = document.getElementsByTagName("head")[0];
            var oScript = document.createElement('script');
            // oScript.type = 'text/javascript';
            oScript.src = sScriptSrc;

            // most browsers
            oScript.onload = oCallback;

            // IE 6 & 7
            oScript.onreadystatechange = function() {
                if (this.readyState == 'complete') {
                    oCallback();
                }
            }
            oHead.appendChild(oScript);
        },

        createCookie: function(name, value, days) {
          if (days) {
            var date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            var expires = "; expires="+date.toGMTString();
          }
          else var expires = "";
          document.cookie = name+"="+value+expires+"; path=/";
        },
        readCookie: function(name) {
          var nameEQ = name + "=";
          var ca = document.cookie.split(';');
          for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
          }
          return null;
        },
        eraseCookie: function(name) {
          SP.createCookie(name,"",-1);
        },

        /**
         * level-based logging; 1: always, 2: warning, 3: debug
         */
        log: function(msg, level) {
          if (level <= logLevel)
            console.log(msg);
        },

        getUrlParams: function() {
          var urlParams = {};
          var e,
            a = /\+/g,  // Regex for replacing addition symbol with a space
            r = /([^&;=]+)=?([^&;]*)/g,
            d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
            q = (window.location.search) ? window.location.search.substring(1) : window.location.hash.split('?')[1];
          while (e = r.exec(q))
            urlParams[d(e[1])] = d(e[2]);
          return urlParams;
        },

        /**
         * print the string... IF exists
         */
        p: function(str) {
          return (str) ? str : "";
        },


        // rendering ------------------------------------------------------------------------
        
        // BEER: retrieving whole object now so i can refactor to only store ids elsewhere...
        // BEER: still need to check for a skipspicks match for yahoo results
        getDetail: function(id, type) {
          SP.log('id: ' + id + '; type: ' + type, 2);
          SP.log('getDetail(' + id + ')', 3);
          // if it's a yahoo, see if there's an identical skipspick
          if (type == 'yahoo') {
            SP.getLocationFromDB(id, function(loc) {
              SP.locationRestRequest({qt: escape(loc.name)}, function(spLoc) {
                if (spLoc.length > 0) {
                  SP.log('found a match, rendering!', 3);
                  spLoc[0].display_id = id; // BEER: hackish?  passing pages initial id to match div ids
                  SP.renderDetail(spLoc[0]);
                } else {
                  SP.log('no match, grab from yahoo and render', 3);
                  SP.getYahooLocal(null, { "id": id }, 'SP.getSingleYahooForRender');
                }
              });
            });
          } else {
            // grab it from spdb
            SP.locationRestRequest({id: id}, function(locations) {
              SP.log("checking for existing: " + id, 3);
              if (locations.length > 0) {
                locations[0].display_id = id;
                SP.renderDetail(locations[0]);
              } else {
                // no results!!
                SP.renderDetail({'key': id});
              }
            });
          }
        },

        // BEER: move this down?
        getSingleYahooForRender: function(yahoo) {
          SP.log('getSingleYahooForRender()', 3);
          SP.log(yahoo, 3);

          var item = yahoo.ResultSet.Result;
          var mapped = SP.convertYahooLocationToSPLocation(item);
          mapped.display_id = mapped.yahoo_id;
          SP.addLocationToDB(mapped); // BEER: overkill?  needed for key attr on object that is used on fav button..
          SP.renderDetail(mapped);
        },

        renderDetail: function(obj) {
          SP.log('rendering detail object: ' + obj.key, 3);
          SP.log(obj, 3);

          $('#details-' + obj.display_id).html(tmpl("detail_tmpl", obj)).page(); // .page("destroy");
          $('h2#detailLocName-' + obj.display_id).text(obj.name); // .page();

          // BEER: fix all the repetitive.  grab the element once and reuse!
          SP.getFavoriteFromDB(obj.key, function(o) {
            var favLink = $('#favorite-' + obj.key);
            var favButton = $('#favbutton-' + obj.key);
            if (o) {
              favLink.attr('data-icon', 'delete'); // BEER: how to change icon??!!
              favLink.text('Remove from Favorites').button();
              favLink.bind('click', function() {
                SP.removeFavoriteFromDB(obj.key);
                favButton.remove();
              });
            } else {
              favLink.button();
              favLink.bind('click', function() {
                SP.addFavoriteToDB(obj);
                favButton.remove();
              });
            }
          });
          $('#details-' + obj.display_id).page().page("destroy");
        },

        renderNearby: function(yahoo) {
          SP.log('original yahoo object:', 3);
          SP.log(yahoo, 3);

          var locations = [];
          var items = yahoo.ResultSet.Result;
          for ( var i = 0; i < items.length; i++ ) {
            var item = items[i];
            var mapped = SP.convertYahooLocationToSPLocation(item);
            SP.addLocationToDB(mapped);
            locations.push(mapped);
          }

          // not sure which is the magic here...
          $('ul#nearbys').html(tmpl("list_tmpl", locations)); // .page();
          $('ul#nearbys').listview('refresh');
          // $('ul#nearbys').listview();
          // $('#nearby.html').page();


          $.mobile.pageLoading(true);
        },

        /**
         * BEER: split render from function so can call getnearby from search w/ a query string
         */
        getNearby: function() {
          // { "qt": "clyde's prime rib" }
          SP.log('renderNearby()', 3);
          $.mobile.pageLoading(); // show loading dialog
          SP.withGeoLocation(function(geoposition) {
            SP.log(geoposition, 3);
            SP.getYahooLocal(geoposition, null, 'SP.renderNearby');
          });
        },

        /**
         * takes either a geopos OR an id
         * callbackName is a string name of func to call
         * query: {id, qt}
         */
        getYahooLocal: function (geoposition, query, callbackName) {
          var url = "http://local.yahooapis.com/LocalSearchService/V3/localSearch";
          var yahooSkipsPicksId = "hK.bnA7V34EQYsQLfVbdFoAgAkItOiaQ.pSUBVClWeLuB..rD8P.85pliVIZeg--";
          url += "?appid=" + yahooSkipsPicksId + "&results=15&radius=3.5&sort=distance&output=json";
          
          if (geoposition) {
            var geo = geoposition.coords;
            url += "&latitude=" + geo.latitude + "&longitude=" + geo.longitude;
          }

          url += "&query=restaurant";
          if (query) {
            if (query.qt)
              url += "+" + escape(query.qt);
            if (query.id)
              url += "&listing_id=" + query.id;
          }

          url += "&callback=" + callbackName;

          SP.log(url, 3);
          SP.loadScript(url, null); // this will cross-domain load the script and call test
        }

    };

}());
