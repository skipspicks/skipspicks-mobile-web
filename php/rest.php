<?php

/**
 * Skip's Picks Rest-style API
 * ===
 *
 * @urls
 * /rest/v1/locations
 * /rest/v1/locations/[id]
 * @parameters: [latlng, radius, qt]
 *
 * /rest/v1/users
 * /rest/v1/users/<string name>
 * @parameters: [email, password]
 *
 * @request: contains 'version' by default
 *
 * @response json response
 *
 */

	require_once('lib.php');
	require_once('db.php');

	// get request method - GET', 'HEAD', 'POST', 'PUT', 'DELETE'
	$method   = $_SERVER['REQUEST_METHOD'];
	$api      = $_REQUEST['api'];
	$version  = $_REQUEST['version'];

	logdump('obj: ' . $obj . '; method: ' . $method . '; id: ' . $id, false);
	logdump($_REQUEST);


    if ($api == 'loc') {
        // POST
        if ($method == 'POST') {
            header("Content-Type: text/html");
              replace_location($_REQUEST, false); // 2nd param means ?dry run?

            ?>

            <!DOCTYPE html>
            <html>
              <head>
                <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
                <title>Skip's Picks - Add</title>
              </head>
              <body>

                <div data-role="page" id="post" data-theme="e">
                  <div data-role="header">
                    <h1>Skip's Picks Mobile - Added</h1>
                    <a href="/m/" rel="external" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
                  </div>
                  <div data-role="content" class="ui-content" role="main">
                    <h1>Thank You!</h1>
                    <p>We appreciate the review, add more of your favorite places.</p>
                  </div>
                  <div data-role="footer">
                  </div>
                </div>

              </body>
            </html>

            <?php
            return;
        }

        // GET;
        logdump($_REQUEST);
        header("Content-Type: application/json");
        $res = to_json(get_locations($_REQUEST));

        print_r($res);
    } else if ($api == 'user') {
        $r =& $_REQUEST;
        header("Content-Type: application/json");
        if ($method == 'POST') {
            // POST
            $password = sha1($r['password']);
            $result = create_user($r['name'], $password, $r['email']);
            if ($result) {
                $result[0]['status'] = 'okay';
                echo json_encode($result[0]);
            } else {
                echo '{"status": "fail"}';
            }
        } else {
            // GET
            $password = ($r['enc'] == 'false') ? $r['password'] : sha1($r['password']);
            $result = get_user($r['name'], $password);
            if ($result) {
                $result[0]['status'] = 'okay';
                echo json_encode($result[0]);
            } else {
                echo '{"status": "fail"}';
            }

        }
    } else if ($api == 'twitter') {
        $status = $_REQUEST['status'];
        post_tweet($status);
    }

?>
