<?php
  header('Content-Type: text/html; charset=UTF-8');
  header('Cache-control: no-cache, no-transform');
?>
<!DOCTYPE html> 
<html> 
  <head> 
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="HandheldFriendly" content="true"/>
    <meta name="description" content="Skip's Picks Mobile lets you find and add restaurant, bar and service reviews, all from a mobile device.">

	<link rel="shortcut icon" href="/favicon.ico" />
	<link rel="icon" href="/favicon.ico" />

    <title>Skip's Picks Mobile</title> 
    <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a3/jquery.mobile-1.0a3.min.css" />
    <script src="http://code.jquery.com/jquery-1.5.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.0a3/jquery.mobile-1.0a3.min.js"></script>

<!--
    <link rel="stylesheet" href="js-libs/jqm1.0a3/jquery.mobile-1.0a3.min.css" />
    <script src="js-libs/jqm1.0a3/jquery-1.5.min.js"></script>
    <script src="js-libs/jqm1.0a3/jquery.mobile-1.0a3.js"></script>
 -->

<!--
    <script src="js-libs/json2.js"></script> 
    <script src="js-libs/micro-template.js"></script> 
    <script src="js-libs/lc_prod.js"></script> 
    <script src="js-libs/sp-mobile.js"></script> 
 -->

<!--
 -->
    <script src="js-libs/sp-mobile.js.combined.min.js"></script> 

  </head> 
  <body>

    <div data-role="page" id="home" data-theme="b">

      <div id="homeheader" data-role="header">
        <h1>Skip's Picks Mobile</h1>
        <form id="search-form" onsubmit="SP.preProcessSubmitForm(this)" action="search.php" method="get">
          <fieldset data-role="fieldcontain">
            <input id="qt" type="search" name="qt" autocomplete="on" placeholder="search..." />
            <button data-inline="true" id="subbutton" type="submit" data-icon="check" data-iconpos="right">Search</button>
          </fieldset>
        </form>
      </div>

      <div data-role="content" data-theme="e"> 
        <ul id="homeLinks" data-role="listview" data-inset="true" data-theme="e" data-inc="">
          <li><a href="nearby.html?">Find Nearby</a></li>
          <li><a href="favorites.html">Favorites</a></li>
          <li><a href="user.html">Log in/out or Create Account</a></li>
          <li><a href="loc-dialog.html" data-rel="dialog" data-transition="pop">Set your location</a></li>
        </ul>
      </div>

      <div data-role="footer">
      </div>
    </div>



    <script type="text/html" id="list_tmpl">
        <% 
          SP.log('building list', 3);
          for ( var i = 0; i < obj.length; i++ ) { 
          var item = obj[i]; 
          %>
          <li role="option" tabindex="<%= i %>" data-theme="c">
            <h3><a href="detail.php?id=<%= item.key %>&type=<%= item.type %>"><%= item.name %></a></h3>
            <p><%= item.address %></p>
            <p><%= item.city %>, <%= item.state %> &nbsp; <b><%= parseFloat(item.distance).toFixed(2) %> miles</b></p>
          </li>
        <% } %>
    </script>

    <script type="text/html" id="detail_tmpl">
      <% 
        var w = Math.floor($(window).width() - 40);
        w = (w > 640) ? 640 : w;
        /* var h = Math.floor($(window).height() / 3); */
        var h = 200;
       %>

      <div id="favbutton-<%= obj.key %>" style="float:right;" data-inline="true">
        <a id="favorite-<%= obj.key %>" href="#" data-inline="true" data-role="button" data-icon="star">Add to Favorites</a>
      </div>

      <h2><%= obj.name %></h2>
      <% if (obj.avg_rev_rating && obj.avg_rev_rating != "NaN") { %>
        <span>Rating: <b><%= (Math.round(obj.avg_rev_rating * 10) / 10).toFixed(1) %></b></span> &nbsp; 
      <% } %>
      <% if (obj.price && obj.price.length > 0) { %>
        <span>Price: <b><%= obj.price[0].description %></b></span> &nbsp; 
      <% } %>
      <span>Created by <b><%= obj.user_name %></b> on <%= obj.create_time %></span> &nbsp; 
      <div>
        <h3>Address</h3>
        <p>
          <%= obj.address %>
          <br />
          <%= obj.city %>, <%= obj.state %> <%= obj.postal_code %>
        </p>
      </div>

      <% 
        if (obj.cuisine && obj.cuisine.length > 0) { 
      %>
        <div data-role="collapsible" data-collapsed="true">
          <h3>Cuisine</h3>
          <p>
            <%
              for ( var i = 0; i < obj.cuisine.length; i++ ) { 
              %>
                <%= obj.cuisine[i].description %><% if (i < obj.cuisine.length - 1) { %>, <% } %>
              <% 
              } 
            %>
          </p>
        </div>
      <% 
        } 
      %>

      <% 
        if (obj.detail && obj.detail.length > 0) { 
      %>
        <div data-role="collapsible" data-collapsed="true">
          <h3>Detail</h3>
          <p>
            <%
              for ( var i = 0; i < obj.detail.length; i++ ) { 
              %>
                <%= obj.detail[i].description %><% if (i < obj.detail.length - 1) { %>, <% } %>
              <% 
              } 
            %>
          </p>
        </div>
      <% 
        } 
      %>

      <p>
        <a href="tel:<%= obj.phone %>" rel="external"><%= obj.phone %></a>
        <br />
        <a href="<%= obj.url %>" rel="external"><%= obj.url %></a>
      </p>

      <p>
        <% if (SP.readCookie('user-name')){ %>
          <h3><a href="add.php?id=<%= obj.key %>">Add a review!</a></h3>
        <% } else { %>
          <h3><a href="user.html">Log in to review</a></h3>
        <% } %>
      </p>

      <% 
        if (obj.reviews) {
          for ( var i = 0; i < obj.reviews.length; i++ ) { 
            var review = obj.reviews[i];
            %>
              <p><%= review.body %></p>
              <% if (review.user_name) { %>
                <p>Posted by <%= review.user_name %>, <%= SP.p(review.update_time) %>; Rating: <%= SP.p(review.rating) %>
              <% } %>
            <% 
          } 
        }
      %>

      <p>
        <img style="margin-left:auto; margin-right:auto;" src="http://maps.google.com/maps/api/staticmap?center=<%= obj.lat %>,<%= obj.lng %>&zoom=14&size=<%= w %>x<%= h %>&maptype=roadmap&markers=color:red|label:R|<%= obj.lat %>,<%= obj.lng %>&sensor=true" width="<%= w %>" height="<%= h %>" />
      </p>

    </script>

    <div data-role="static-footer" id="static-footer">
      <div style="margin:.3em;">
        <a href="http://www.twitter.com/skipspicks"><img src="http://twitter-badges.s3.amazonaws.com/t_logo-a.png" alt="Follow ne on Twitter"/></a>
      </div>
    </div>

  </body> 
</html> 
