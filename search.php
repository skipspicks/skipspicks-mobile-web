<!DOCTYPE html> 
<html> 
  <head> 
    <meta charset=UTF-8"/> 
    <title>Skip's Picks Mobile - Search Results</title> 
  </head> 
  <body>

    <!-- Start of first page -->
    <div data-role="page" id="search" data-theme="e">

      <div data-role="header" data-theme="b">
        <h1>Search Results</h1>
        <a id="filterButton" href="filter.php?qt=<?= $_REQUEST['qt']; ?>" data-icon="arrow-d" data-rel="dialog" class="ui-btn-right">Filter</a>
      </div>

      <div data-role="content">

        <script>
          var lid     = "<?= $_REQUEST['id']; ?>";
          var qt      = "<?= $_REQUEST['qt']; ?>";
          var details = "<?= implode(',', (array)$_REQUEST['detail']); ?>";
          var sort    = "<?= $_REQUEST['sort']; ?>";
          var rating  = "<?= $_REQUEST['rating']; ?>";
          var price   = "<?= $_REQUEST['price']; ?>";

          var f = {};
          f.qt = qt;
          f.id = lid;
          f.details = details;
          f.rating = rating;
          f.price = price;
          f.sort = sort;
          SP.doSearch(f);
        </script>

        <ul data-role="listview" data-filter="true" id="searchresults">
          Loading...
        </ul>
      </div>

      <div data-role="footer" data-theme="b">
      </div>
    </div>

  </body> 
</html> 
