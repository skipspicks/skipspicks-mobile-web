<?php
  $id = $_REQUEST['id'];

// get from db?  or REST?
$loc_types = array(
  "74" => "Restaurant",
  "75" => "Bar",
  "81" => "Hotel",
  // "76" => "Spa",
  // "79" => "Brew 'n' View",
  // "80" => "Bowling Alley",
  // "77" => "Skate Shop"
);

$rating = array(
  "62" => "1",      
  "63" => "2",      
  "64" => "3",      
  "65" => "4",      
  "66" => "5"
);

$details = array(
    1 => "Breakfast",
    5 => "Lunch",
    3 => "Late Food",
    2 => "Patio/Outdoor",
    4 => "Counter",
    6 => "Full Bar",
    9 => "Shuffleboard",
    // 10 => "New",
    // 11 => "Wish List",
    13 => "Good Happy Hour",
    // 14 => "Buffet",
    15 => "Street/Cart Food",
    // 16 => "GoTo" 
);

$cuisines = array(  
  "17" => "American",
  "19" => "Bbq",  
  "22" => "Cajun",
  // "23" => "Chinese",  
  "24" => "Coffee",
  // "25" => "Comfort",  
  // "26" => "Creole",
  "27" => "Cuban",  
  "29" => "Deli",
  "30" => "Desert",
  "31" => "Dim sum",
  // "32" => "Espresso/wine/beer",
  "33" => "French",
  "36" => "Indian",
  "37" => "Italian",
  // "38" => "Japanese",
  // "39" => "Korean",
  "40" => "Lebanese",
  "41" => "Mediterranean",
  "42" => "Mexican",
  // "43" => "Noodle",
  "44" => "Northwest",
  "45" => "Nouveau",
  // "46" => "Pan asian",
  "47" => "Pizza",
  "48" => "Seafood",
  "49" => "Seasonal/local",
  "52" => "Southern",
  "53" => "Steak",
  // "54" => "Surf/turf",
  "55" => "Sushi",
  // "57" => "Tapas/small plate",
  "58" => "Thai",
  "59" => "Vegetarian",
  // "60" => "Vietnamese",
  "87" => "Asian"
);

$price = array(
  82 => '$',      
  83 => '$$',     
  84 => '$$$'
);

$detail_list = array(
  array("title" => "Type",      "type" => "checkbox",     "list" => $loc_types, "name" => "detail[]", "default" => 0), 
  array("title" => "Details",   "type" => "checkbox",     "list" => $details, "name" => "detail[]", "default" => 0), 
  array("title" => "Cuisines",  "type" => "checkbox",     "list" => $cuisines, "name" => "detail[]", "default" => 0), 
  array("title" => "Rating",    "type" => "radio-choice", "list" => $rating, "name" => "rating", "default" => 62), 
  array("title" => "Price",     "type" => "radio-choice", "list" => $price, "name" => "detail[]", "default" => 82, "new_only"=> true)
);

$max_horizontal = 5;
?>
<!DOCTYPE html> 
<html> 
  <head> 
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/> 
    <title>Skip's Picks - Add</title> 
  </head> 
  <body>

    <div data-role="page" id="add" data-theme="d">

      <div data-role="header" data-theme="e">
        <h1>Add</h1>
        <a href="/" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
      </div>

      <div data-role="content" class="ui-content" role="main">
      <div>
        <form id="addForm" action="/rest/v1/locations/<?= $id; ?>" method="post" onsubmit="return false;">
          <fieldset>
            <input id="addLocation_id" type="hidden" name="location_id" value="" />
            <input id="addLat" type="hidden" name="lat" value="" />
            <input id="addLng" type="hidden" name="lng" value="" />
            <input id="addUser" type="hidden" name="user" value="" />
            <input id="addUserId" type="hidden" name="user_id" value="" />

            <input id="addName" type="text" name="name" tabindex="1" autocomplete="on" placeholder="name" />
            <input id="addAddress" type="text" name="address" tabindex="2" autocomplete="on" placeholder="address" />
            <input id="addCity" type="text" name="city" tabindex="3" autocomplete="on" placeholder="city" />
            <input id="addState" type="text" name="state" tabindex="4" autocomplete="on" placeholder="state" />
            <input id="addPostal_code" type="text" name="postal_code" tabindex="5" autocomplete="on" placeholder="postal code" />
            <input id="addUrl" type="text" name="url" tabindex="6" autocomplete="on" placeholder="url" />
            <input id="addHours" type="text" name="hours" tabindex="7" autocomplete="on" placeholder="hours" />
            <input id="addPhone" type="text" name="phone" tabindex="8" autocomplete="on" placeholder="phone" />
          </fieldset>

          <div data-role="collapsible-set">
            <?php 
              foreach ($detail_list as $map) {
                if (!empty($id) && $map['new_only'])
                    continue;
            ?>
            <div data-role="collapsible" data-collapsed="true" style="width:80%;margin-left:auto;margin-right:auto;">
              <h3><?= $map['title']; ?></h3>
              <div  data-role="fieldcontain"> 
                <fieldset data-role="controlgroup"<?= (count($map['list']) <= $max_horizontal) ? ' data-type="horizontal"' : ''; ?>> 
                  <?php
                    foreach ($map['list'] as $key => $value) { 
                      $split = explode('-', $map['type']);
                      $itype = $split[0];
                      $name = $map['name'];
                  ?>
                    <input type="<?= $itype; ?>" name="<?= $name; ?>" id="<?= $map['type']; ?>-<?= $key; ?>" class="custom" value="<?= $key; ?>" <?= (($key == $map['default']) ? 'checked="checked" ' : ""); ?>/> 
                    <label for="<?= $map['type']; ?>-<?= $key; ?>"><?= $value; ?></label> 

                  <?php } ?>
                </fieldset> 
              </div> 
            </div> 
            <?php } ?>
          </div> 

          <textarea id="addReview" name="review" placeholder="review"></textarea>

          <button type="submit">Submit</button>

        </form>
      </div>

        <script>
          $('#addForm').submit(function(e) {
            e.preventDefault();
            console.log('caught!');
            SP.preProcessAddForm($(this));
            return false;
          });
          SP.withGeoLocation(function(geoposition) {
            if (!geoposition)
              return;
            var g = geoposition.address;
            $('#addAddress').val(g.streetNumber + ' ' + g.street);
            $('#addCity').val(g.city);
            $('#addState').val(g.region);
            $('#addPostal_code').val(g.postalCode);
            SP.log(g, 2);
          });
        </script>

        <?php if (!empty($id)) { ?>
          <script>
            id = <?= $id; ?>;
          </script>
        <?php } ?>

      </div>

      <div data-role="footer" data-theme="e">
      </div>
    </div>

  </body> 
</html> 
