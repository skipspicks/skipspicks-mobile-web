<?php
/**
 *
 *
 * NOTE: need to create class for userlocation with a to_json()
 */


    function prepare_data(&$item, $key) {
      $item = html_entity_decode(stripslashes(nl2br($item)));
    }

    /**
     * @return location object
     */
    function to_json($data) {
        $nl = "\n";

        // BEER/TODO: i could nest the locations key into the array and avoid even more markup here
        $out  = '{';
        $out .= $nl;
        $out .= '  "locations": [';
        $out .= $nl;

        foreach ($data as $loc) {
            array_walk_recursive($loc, 'prepare_data');
            $out .= json_encode($loc);
            $out .= $nl;
            if (++$dcount < count($data)) { $out .= ','; }
            $out .= $nl;
        }


        $out .= '  ]';
        $out .= $nl;
        $out .= '}';

        return $out;
    }

    /**
     * log to /tmp/php.log
     *
     * @param msg text to log
     * @param echo optional default false
     *
     */
    function logdump($msg, $echo = false) {
        error_log(print_r($msg, true) . "\n", 3, "/tmp/php.log");
        if ($echo)
            print_r($msg) . "\n";
    }

    /**
     * Twitter
     * uses Oauth lib
     */
    function post_tweet($status) {
        require_once('twitteroauth/twitteroauth.php');
        require_once('twitteroauth/config.php');
        // $connection = new TwitterOAuth(SPTEST_CONSUMER_KEY, SPTEST_CONSUMER_SECRET, SPTEST_OAUTH_TOKEN, SPTEST_OAUTH_TOKEN_SECRET);
        $connection = new TwitterOAuth(SP_CONSUMER_KEY, SP_CONSUMER_SECRET, SP_OAUTH_TOKEN, SP_OAUTH_TOKEN_SECRET);
        $connection->format = 'xml';
        $connection->decode_json = FALSE;
        $rc = $connection->post('statuses/update', array('status' => $status));
        logdump($rc);
    }

?>
