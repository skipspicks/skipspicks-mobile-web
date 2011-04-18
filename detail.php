<?php
  $id   = $_REQUEST['id']; 
?>
<!DOCTYPE html> 
<html> 
  <head> 
    <meta charset=UTF-8"/> 
    <title>Skip's Picks Mobile - Detail</title> 
  </head> 
  <body>

    <!-- Start of first page -->
    <div data-role="page" id="detail" data-theme="e">

      <div data-role="header" data-theme="b">
        <h2 id="detailLocName-<?= $id; ?>">Title</h2>
        <a href="/" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
      </div>

      <div data-role="content" role="main">

        <div id="details-<?= $id; ?>">
          Loading...
        </div>

      </div>

      <div data-role="footer" data-theme="b">
      </div>

    </div>

  </body> 
</html> 
