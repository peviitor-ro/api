<?php
header("Access-Control-Allow-Origin: *");


function validate_api_key($key)
{
    $method = 'GET';
    global $server;

    require_once '../config.php';

    $core  = 'auth';
    $command = '/select';

    $qs = '?';
    $qs .= 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=apikey%3A"';
    $qs .= $key;
    $qs .= '"&';
    $qs .= 'rows=0';

    $url =  'http://' . $server . '/solr/' . $core . $command . $qs;

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


function get_user_from_api_key($key)
{
    $method = 'GET';
    global $server;

    require_once '../config.php';

    $core  = 'auth';
    $command = '/select';

    $qs = '?';
    $qs .= 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=apikey%3A"';
    $qs .= $key;
    $qs .= '"&rows=1';

    $url = 'http://' . $server . '/solr/' . $core . $command . $qs;

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
        $x = $json->response->docs[0]->id;
    }
    if ($y == 0) {
        $x = false;
    }

    return $x;
}

function get_company($token)
{
    $x = $company = $_POST['company'];
    return $x;
}


function company_exist($company)
{
    global $server;
    require_once '../config.php';

    $core = 'jobs';

    $qs = '?';
    $qs .= 'indent=true';
    $qs .= '&';
    $qs .= 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=company%3A%22';

    $q = '%22';
    $q .= '&';
    $q .= 'rows=0';
    $q .= '&';
    $q .= 'useParams=';

    $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $company . $q;

    $string = file_get_contents($url);
    $json = json_decode($string, true);

    if ($json['response']['numFound'] == 0) {
        return "new";
    }
    if ($json['response']['numFound'] != 0) {
        return "existing";
    }
}

function clean($xcompany, $key)
{
    $method = 'POST';
    global $server;

    require_once '../config.php';

    $core  = 'jobs';
    $command = '/update';

    $qs = '?';
    $qs .= '_=1617366504771';
    $qs .= '&';
    $qs .= 'commitWithin=100';
    $qs .= '&';
    $qs .= 'overwrite=true';
    $qs .= '&';
    $qs .= 'wt=json';

    $data = "{'delete': {'query': 'company:";
    $data .= $xcompany;
    $data .= "'}}";

    //echo $data;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );

    $msg = '';

    if (company_exist($xcompany) == "new") {
        $msg .= "!!! COMPLETELY NEW  !!!";
    }

    $msg .= $xcompany . '  user: ' . get_user_from_api_key($key);
    $context  = stream_context_create($options);

    $url = 'http://' . $server . '/solr/' . $core . $command . $qs;

    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */
    }
}

// endpoint starts here

foreach (getallheaders() as $name => $value) {
    if (($name == 'apikey')) {
        if (validate_api_key($value) == true) {
            $company = get_company($value);
            clean($company, $value);
        } else {
            echo "apikey error";
        }
    }
}
