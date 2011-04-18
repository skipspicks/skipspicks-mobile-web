<?php
  header('Content-Type: text/html; charset=UTF-8');
  header('Cache-control: no-cache, no-transform');
?>
<!DOCTYPE html> 
<html> 
  <head> 
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta name="HandheldFriendly" content="true"/>
    <meta name="description" content="">

    <title>Skip's Picks Mobile</title> 

    <script src="http://maps.google.com/maps/api/js?sensor=false"></script>

    <script>
alert('shit');
var elevator;
var map;
var infowindow = new google.maps.InfoWindow();
var denali = new google.maps.LatLng(37.4216227, -122.0840263);

function initialize() {
  var myOptions = {
    zoom: 8,
    center: denali,
    mapTypeId: 'terrain'
  }
  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

  // Create an ElevationService
  elevator = new google.maps.ElevationService();

  // Add a listener for the click event and call getElevation on that location
  google.maps.event.addListener(map, 'click', getElevation);
}

function getElevation(event) {

  var locations = [];

  // Retrieve the clicked location and push it on the array
  var clickedLocation = event.latLng;
  locations.push(clickedLocation);

  // Create a LocationElevationRequest object using the array's one value
  var positionalRequest = {
    'locations': locations
  }

  // Initiate the location request
  elevator.getElevationForLocations(positionalRequest, function(results, status) {
    if (status == google.maps.ElevationStatus.OK) {

      // Retrieve the first result
      if (results[0]) {

        // Open an info window indicating the elevation at the clicked position
        infowindow.setContent("The elevation at this point 
is " + results[0].elevation + " meters.");
        infowindow.setPosition(clickedLocation);
        infowindow.open(map);
      } else {
        alert("No results found");
      }
    } else {
      alert("Elevation service failed due to: " + status);
    }
  });
}

    </script>
  </head> 
  <body onload="initialize();">

    <div id="map_canvas"></div>

  </body> 
</html> 
