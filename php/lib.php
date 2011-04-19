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

    function get_yahoo_nearby($lat = 45.5217631, $lng = -122.6781892, $radius = 3.5) {
        $http_info = array();
        $ci = curl_init();
        $url = 'http://local.yahooapis.com/LocalSearchService/V3/localSearch?appid=hK.bnA7V34EQYsQLfVbdFoAgAkItOiaQ.pSUBVClWeLuB..rD8P.85pliVIZeg--&results=15&radius=' . $radius . '&sort=distance&output=php&latitude=' . $lat . '&longitude=' . $lng . '&query=restaurant';

        /* Curl settings */
        curl_setopt($ci, CURLOPT_USERAGENT, 'User-Agent:Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; en-US) AppleWebKit/534.
        16 (KHTML, like Gecko) Chrome/10.0.648.205 Safari/534.16');    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, false);
        curl_setopt($ci, CURLOPT_URL, $url);

        $response = curl_exec($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $http_info = array_merge($http_info, curl_getinfo($ci));
        $url = $url;
        curl_close ($ci);
        return $response;
    }


?>
