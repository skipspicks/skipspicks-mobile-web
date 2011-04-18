<?php
  $qt = $_REQUEST['qt'];

$rating = array(
  "62" => "1",      
  "63" => "2",      
  "64" => "3",      
  "65" => "4",      
  "66" => "5"
);
$details = array(
    1 => "Breakfast",
    2 => "Patio/Outdoor",
    3 => "Late Food",
    4 => "Counter",
    5 => "Lunch",
    6 => "Full Bar",
    9 => "Shuffleboard",
    11 => "Wish List",
    13 => "Good Happy Hour",
    15 => "Street/Cart Food",
    16 => "GoTo" 
);
$price = array(
  "82" => '$',      
  "83" => '$$',     
  "84" => '$$$'
);
$sort = array(
  'rating',
  'price',
  'match',
  'distance'
);
$filters = array(
  'Sort by' => array('type' => 'radio-choice', 'details' => $sort, 'name' => 'sort', 'default' => 0),
  'Rating'  => array('type' => 'radio-choice', 'details' => $rating, 'name' => 'rating', 'default' => 62),
  'Detail'  => array('type' => 'checkbox', 'details' => $details, 'name' => 'detail[]', 'default' => 0),
  'Price'   => array('type' => 'radio-choice', 'details' => $price, 'name' => 'price', 'default' => 82),
);

?>
<!DOCTYPE html> 
<html> 
  <head> 
    <meta charset=UTF-8"/> 
    <title>Skip's Picks Mobile - Search Results</title> 
  </head> 
  <body>

    <div data-role="page" id="search-filter"> 
      <div data-role="header" data-theme="d" data-position="inline"> 
        <h1>Filters</h1> 
      </div> 
      <div data-role="content" data-theme="c"> 

        <form id="filter-form" onsubmit="$('.ui-dialog').dialog('close'); SP.preProcessSubmitForm(this)" action="search.php?qt=<?= $qt; ?>" method="POST">
          <p>Filter results further by...</p>
            <?php 
              foreach ($filters as $key => $map) {
                $split = explode('-', $map['type']);
                $itype = $split[0];
                $name = $map['name'];
            ?>
              <h3><?= $key; ?></h3>
                <fieldset data-role="controlgroup"<?= (count($map['details']) <= 5) ? ' data-type="horizontal"' : ''; ?>> 
                  <?php 
                    foreach ($map['details'] as $id => $label) {
                  ?>
                    <input type="<?= $itype; ?>" name="<?= $name; ?>" id="<?= $map['type']; ?>-<?= $id; ?>" class="custom" value="<?= $id; ?>" <?= (($id == $map['default']) ? 'checked="checked" ' : ""); ?>/> 
                    <label for="<?= $map['type']; ?>-<?= $id; ?>"><?= $label; ?></label> 
                  <?php } ?>
                </fieldset>
            <?php } ?>
          <button type="submit">Submit</button>
        </form>
      </div> 
    </div> 

  </body> 
</html> 
