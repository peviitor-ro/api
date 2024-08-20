<?php
header("Access-Control-Allow-Origin: *");

function validate_api_key($key)
{
    $method = 'GET';

    require_once '../config.php';

    $core  = 'auth';
    $command = '/select';

    $qs = '?';
    $qs .= 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=apikey%3A"';
    $qs .= $key;
    $qs .= '"&rows=0';

    $url = 'http://' . $server . $core . $command . $qs;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */
    }

    $json = json_decode($result);
    $y = $json->response->numFound;

    if ($y == 1) {
        $x = true;
    }
    if ($y == 0) {
        $x = false;
    }

    return $x;
}

function get_company($token)
{
    //go to database
    //get the company code based on $token

    $method = 'GET';

    require_once '../config.php';

    $core  = 'auth';
    $command = '/select';

    $qs = '?';
    $qs .= $qs . 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=apikey%3A"';
    $qs .= $token;
    $qs .= '"%26rows%3D1';

    $url = 'http://' . $server . $core . $command . $qs;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $qs
        )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */
    }

    $json = json_decode($result);
    $x = $json->response->docs[0]->company[0];

    return $x;
}

function clean($xcompany)
{
    $method = 'POST';

    require_once '../config.php';

    $core  = 'jobs';
    $command = '/update';

    $qs = '?';
    $qs .= '_=1617366504771';
    $qs .= '&';
    $qs .= 'commitWithin=1000';
    $qs .= '&';
    $qs .= 'overwrite=true';
    $qs .= '&';
    $qs .= 'wt=json';

    $url =  'http://' . $server . $core . $command . $qs;

    $data = "{'delete': {'query': 'company:";
    $data .= $xcompany;
    $data .= "'}}";

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );
    $context = stream_context_create($options);

    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */
    }

    var_dump($result);
}

// endpoint starts here
foreach (getallheaders() as $name => $value) {
    if (($name == 'apikey')) {
        if (validate_api_key($value) == true) {
            $company = get_company($value);
            clean($company);
        } else {
            echo "apikey error";
        }
    }
}
