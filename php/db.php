<?php
/**
 *
 *
 */

	require_once('mysql_connect.php');

    /**
     * for rest-ful GET of user locations.  no params means get all
     *
     * /rest/locations[/<id>]
     *
     * @param map of filter options: id, qt, sort, details
     * @return array userlocation data
     *
     */
    function get_locations($filter) {

        $id       = $filter['id'];
        $qt       = $filter['qt'];
        $user     = $filter['user'];
        $sort     = $filter['sort'];
        $details  = $filter['details'];
        $rating   = $filter['rating'];
        $price    = $filter['price'];
        $join     = false;
        $lat      = $filter['lat'];
        $lng      = $filter['lng'];
        $distance = 25; // BEER defaulting to 25 miles; maybe take as a query param

        $query = "
            select 
              l.location_id,
              l.location_id as 'id',
              l.name,
              l.address,
              l.city,
              l.state,
              l.postal_code,
              l.hours,
              l.phone,
              l.pick,
              l.lat,
              l.lng,
              l.user_id,
              u.user_name,
              l.url,
              l.update_time,
              l.create_time,
              'skipspicks' as type,
              avg(ratd.description) as avg_rev_rating
              ";

        if (!empty($lat)) {
            $query .= " , ( 3959 * acos( cos( radians($lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( lat ) ) ) ) as distance 
              ";
        } else {
            $query .= ", 0 as distance ";
        }

        if ($sort == 'rating')
            $query .= " , r.detail_id as rating ";

        // p.detail_id as price

        $query .= "
            from location l
                left outer join location_review_map lrm on lrm.location_id = l.location_id
                left join review r on r.review_id = lrm.review_id -- inner join dropping non-revew spots; ok?
                left join detail ratd on ratd.detail_id = r.rating_detail_id
                left outer join user u on u.user_id = l.user_id
            ";

        if (is_numeric($id)) {
            $where = " where l.location_id = $id ";
        } else if (!empty($user)) {
            $query .= " 
                inner join user u on u.user_id = l.user_id
                    and u.user_name = '$user'
                ";
        } else {
            $join = array();
            // Haversine formula
            /*
            select location_id,
            ( 3959 * acos( cos( radians(45.5164771) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(-122.6433179) ) + sin( radians(45.5164771) ) * sin( radians( lat ) ) ) ) as distance
            from location
            having distance < $distance
            order by distance
            */


            // details
            if (!empty($details)) {
                // $detail_list = explode(',', $details);
                array_push($join, " 
                    select 
                        d.detail_id
                    from detail d 
                    where d.detail_type_id in (1, 2)
                        and d.detail_id in ( $details )
                  ");
            }

            // rating
            if (!empty($rating)) {
                array_push($join, " 
                    select 
                        d.detail_id
                    from detail d 
                    where d.detail_type_id = 3
                        and d.detail_id >= $rating
                  ");
            }

            // price
            if (!empty($price)) {
                $price = (!empty($price)) ? $price : 82; // get any price
                array_push($join, " 
                    select d.detail_id
                    from detail d
                    where d.detail_type_id = 5
                        and d.detail_id >= $price
                  ");
            }

            // array_push($join, " select 10000 as detail_id ");

        }

        if (!empty($join)) {
          $query .= " inner join location_detail_map ldm on ldm.location_id = l.location_id ";
          $query .= " 
                  and ldm.detail_id in (
              ";
          $query .= implode("\n union \n", $join) . " ) ";
        }

        $r = (!empty($rating)) ? $rating : 61; // get any rating
        for (; $r <= 66; $r++)
            $rlist[] = $r;

        if ($sort == 'rating') {
            $query .= " 
                inner join location_detail_map ldmr on ldmr.location_id = l.location_id 
                inner join detail r on r.detail_id = ldmr.detail_id 
                    and r.detail_type_id = 3
                    and r.detail_id in (" . implode(',', $rlist) . ")
                    -- BEER: make this a list of all above given rating

                -- inner join location_detail_map ldmp on ldmp.location_id = l.location_id 
                -- inner join detail p on p.detail_id = ldmp.detail_id 
                    -- and p.detail_type_id = 5
                ";
        }

        if (!empty($qt)) {
            // do some fuzzy logic to match names
            // - lowercase
            // trim all extra space
            $qt = trim($qt);
            $qt =  preg_replace('/(\s+)/', ' ', $qt);
            strtolower($qt);
            $qt = mysql_real_escape_string($qt);
            $where = " where l.name like '%$qt%' "; // case-insensitive; if missing, matches all
        }

        $query .= $where;
        $query .= " group by l.location_id ";

        $query .= "
            having distance < $distance
            ";

        // sorting 
        if ($sort == 'rating') {
            $order .= " order by distance, rating desc ";
        } else if ($sort == 'price') {
            $order .= " order by distance, price desc ";
        } else {
            $order .= " order by distance, update_time desc ";
        }

        $query .= $order;
        $query .= " limit 10 ";

        $locs = query_db($query, true);


        // fill out locs with detail information
        $query = "
          select detail_type_id,
            description
          from detail_type
          ";
        $dtypes = query_db($query, false);

        for ($i = 0; $i < count($locs); $i++) {
          foreach ($dtypes as $dtype) {
            $query = "
              select d.detail_id,
                d.description
              from detail d
                inner join location_detail_map ldm on ldm.detail_id = d.detail_id
                  and ldm.location_id = " . $locs[$i]['location_id'] . "
              where detail_type_id = " . $dtype['detail_type_id'] . "
              ";
            $details = query_db($query, false);
            $locs[$i][$dtype['description']] = $details;
          }

          $query = "
            select r.review_id,
              r.body,
              r.update_time,
              r.user_id,
              u.user_name,
              r.rating_detail_id,
              d.description as rating
            from review r
              inner join location_review_map lrm on lrm.review_id = r.review_id
                and lrm.location_id = " . $locs[$i]['location_id'] . "
              left outer join user u on u.user_id = r.user_id
              left outer join detail d on d.detail_id = r.rating_detail_id
            ";
          $reviews = query_db($query, false);
          $locs[$i]['reviews'] = $reviews;
        }

        $yahoo = unserialize(get_yahoo_nearby($lat, $lng));
        foreach ($yahoo['ResultSet']['Result'] as $y) {
          $yahoo_loc = array(
            yahoo_id => $y['id'],
            location_id => $y['id'],
            name => $y['Title'],
            address => $y['Address'],
            city => $y['City'],
            state => $y['State'],
            phone => $y['Phone'],
            lat => $y['Latitude'],
            lng => $y['Longitude'],
            url => $y['BusinessClickUrl'],
            avg_rev_rating => $y['Rating']['AverageRating'],
            distance => $y['Distance'],
            reviews => array(array(body => $y['Rating']['LastReviewIntro'])),
            type => 'yahoo'
          );

          $cname = preg_replace('/(the|kitchen|restaurant)/i', '', $yahoo_loc['name']);
          $cstreet = preg_replace('/\D/i', '', $yahoo_loc['address']);
          if (!is_dupe($locs, $cname, $cstreet))
            array_push($locs, $yahoo_loc);
        }
        // print_r($yahoo);

        usort($locs, 'cmp');
        // print_r($locs);

        return $locs;
    }

    function is_dupe(&$array, $cname, $cstreet) {
        foreach ($array as $key => $value) {
          $name = preg_replace('/(the|kitchen|restaurant)/i', '', $value['name']);
          $street = preg_replace('/\D/i', '', $value['address']);
          if (preg_match('/'.$street.'/', $cstreet)) {
            if (preg_match('/'.$name.'/', $cname)) {
                logdump('found dupe: ' . $value['name'], false);
                return true;
            } 
          }
        }
        return false;
    }

    function cmp($a, $b) {
        $ad = $a['distance'];
        $bd = $b['distance'];
        if ($ad == $bd) {
            return 0;
        }
        return ($ad < $bd) ? -1 : 1;
    }

    /**
     * takes a flattened location (eg. from a post)
     * and puts it in the db using replace
     *
     * $loc = array(
     *   "id" => "21960067",
     *   "location_id" => 21960067
     *   "name" => "Padthai  Kitchen",
     *   "address" => "2309 Se Belmont St",
     *   ....
     *   "detail" => array(74, 2, 4, 5, 16, 58),
     *   "rating" => 65,
     *   "price" => 83,
     *   "review" => "This place is the yums, bums!",
     * );
     *
     */
    function replace_location($loc, $dry = false) {
        $id = $loc['location_id'];

        // in case someone is adding a brand new one
        if (empty($id)) {
            $res = mysql_query("select location_id from location where name = '". mysql_real_escape_string($loc['name']) . "'", DB_LINK);
            $id = mysql_result($res, 0);
        }

        if (empty($id)) {
            // init loc
            $fields = array("name", "address", "city", "state", "postal_code", "hours", "phone", "pick", "lat", "lng", "url", "update_time", "create_time", "user_id");

            // massage name slightly
            $name = trim($loc['name']);
            $name =  preg_replace('/(\s+)/', ' ', $name);

            $list = implode(', ', $fields);
            $sql = "
                insert into location ($list)
                values (
                  '" . mysql_real_escape_string($name) . "', 
                  '" . mysql_real_escape_string($loc['address']) . "', 
                  '" . mysql_real_escape_string($loc['city']) . "', 
                  '" . mysql_real_escape_string($loc['state']) . "', 
                  '" . mysql_real_escape_string($loc['postal_code']) . "', 
                  '" . mysql_real_escape_string($loc['hours']) . "', 
                  '" . mysql_real_escape_string($loc['phone']) . "', 
                  '" . mysql_real_escape_string($loc['pick']) . "', 
                  " . mysql_real_escape_string($loc['lat']) . ", 
                  " . mysql_real_escape_string($loc['lng']) . ", 
                  '" . mysql_real_escape_string($loc['url']) . "', 
                  now(),
                  now(),
                  " . $loc['user_id'] . "
                  )";
        } else {
            // update
            $fields = array("name", "address", "city", "state", "postal_code", "hours", "phone", "url", "update_time");
            $sql = "
                update location 
                set update_time = now()
                ";
            foreach ($fields as $f) {
                if (!empty($loc[$f]))
                  $sql .= ", $f = '" . mysql_real_escape_string($loc[$f]). "'";
            }
            $sql .= " where location_id = $id ";
        }

        input_db($sql, $dry, true) or die("error on location!");

        if (empty($id))
          $id = mysql_insert_id(DB_LINK);
        logdump($id);

        // handle details
        if (!empty($loc['detail'])) {
          $detail_values = array();
            foreach ($loc['detail'] as $d) {
                $pair = "($id, $d)";
                array_push($detail_values, $pair);
            }
            $vals = implode(', ', $detail_values);
            $sql = "
                replace into location_detail_map (location_id, detail_id)
                values $vals
                ";
            input_db($sql, $dry, true) or die("error on ldm!");
        } 

        // next up, add review; this is where rating goes, not on location
        if (!empty($loc['review'])) {
            $rating = (!empty($loc['rating'])) ? $loc['rating'] : 61;
            $sql = "
                insert into review (body, update_time, user_id, rating_detail_id)
                values ('" . mysql_real_escape_string($loc['review']). "', now(), " . $loc['user_id'] . ", $rating)
                ";
            input_db($sql, $dry, true) or die("error on review!");
            $rev_id = mysql_insert_id(DB_LINK);

            $sql = "
                insert into location_review_map (location_id, review_id)
                values ($id, $rev_id)
                ";
            input_db($sql, $dry, true) or die("error on lrm!");
        }

        // if no errors, post to twitter
        $status = 'A review of ' .  html_entity_decode($loc['name']) . ' was just posted by ' .  $loc['user'];
        $status .= " on Skip's Picks Mobile: ";
        $status .= ' http://m.skipspicks.net/#detail.php?id=' . $id;
        post_tweet($status);
    }

    /**
     * User funcs
     */
    function create_user($user, $password, $email) {
        $sql = "
            insert into user (user_name, password, email)
            values ('" . prep($user). "', '" . $password . "', '" . prep($email) . "')
            ";
        if (input_db($sql, false, true))
            return get_user($user, $password);

        return false;
    }

    /**
     * password comes in as sha1
     */
    function get_user($user, $password) {
        $query = "
            select user_id,
                user_name,
                password,
                email
            from user
            where user_name = '$user'
                and password = '$password'
                ";
        $user = query_db($query, true);

        // get user locations
        if (!empty($user)) {
logdump($user, false);
            $user[0]['locations'] = array();

            // BEER: only getting stuff that has a review; might want to cautiously include non-review adds
            $query = "
                select distinct l.location_id,
                    l.location_id as 'key',
                    'skipspicks' as type,
                    l.name,
                    l.address,
                    l.city,
                    l.state
                ";

            if (!empty($user[0]['lat'])) {
                $query .= " , ( 3959 * acos( cos( radians(" . $user[0]['lat'] . ") ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(" . $user[0]['lng'] . ") ) + sin( radians(" . $user[0]['lat'] . ") ) * sin( radians( lat ) ) ) ) as distance 
                  ";
            } else {
                $query .= ", 0 as distance ";
            }

            $query .= "
                from location l
                    inner join location_review_map lrm on lrm.location_id = l.location_id
                    inner join review r on r.review_id = lrm.review_id
                        and r.user_id = " . $user[0]['user_id'] . "
                group by l.location_id
                order by r.update_time desc
                limit 10
                ";

            $locs = query_db($query, true);
            foreach ($locs as $row)
                array_push($user[0]['locations'], $row);
        }

        return $user;
    }


    /**
     * =================== util functions ======================
     */

    /**
     * run a select and return results in array of associative arrays
     */
    function query_db($query, $log = false) {
        if ($log)
            logdump($query, false);
        $result = mysql_query($query, DB_LINK);
        if (!$result)
            logdump(mysql_error());
        $res = array();
        while ($row = mysql_fetch_assoc($result))
            array_push($res, $row);
        return $res;
    }

    function input_db($sql, $dry = false, $log = false) {
        if ($log)
            logdump($sql, false);
        $result = true;
        if (!$dry) {
            $result = mysql_query($sql, DB_LINK);
        }
        if (!$result) {
            logdump("error adding location " . $loc['name']);
            logdump(mysql_error());
            return false;
        }
        return true;
    }

    /**
     * mostly for shorter alias, but could be a good hook later
     */
    function prep($input) {
        return mysql_real_escape_string($input);
    }

?>
