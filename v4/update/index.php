<?php
header("Access-Control-Allow-Origin: *");
$server = 'zimbor.go.ro';

function validate_api_key($key)
{
    $method = 'GET';

    

    $core  = 'auth';
    $command = '/select';

    $qs = '?';
    $qs .= 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=apikey%3A"';
    $qs .= $key;
    $qs .= '"&rows=0';

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );

    $context  = stream_context_create($options);

    $url = 'http://' . $server . '/solr/' . $core . $command . $qs;

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

    

    $core  = 'auth';
    $command = '/select';

    $qs = '?';
    $qs .= 'q.op=OR';
    $qs .= '&';
    $qs .= 'q=apikey%3A"';
    $qs .= $key;
    $qs .= '"&';
    $qs .= 'rows=1';

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


function discord_webhook($msg)
{
    $msg .= ' UPDATE in PRODUCTION ' . date("l d-m-Y H:i:s");
    $method = 'POST';
    $url = "https://discord.com/api/webhooks/1127143279977308240/etcQT4Roo02_6sy38WwUWwUmaNGKEylEJxJuq_bWw0HZLiynXKPLAt3qnyWpGnRd6X8Y";
    $data = '{"content": "' . $msg . '"}';

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */
    }
}


function update($key)
{
    $method = 'POST';

    

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

    $data = file_get_contents('php://input');

    $json = json_decode($data);

    foreach ($json as $item) {
        $item->job_title  = html_entity_decode($item->job_title);
        $item->country    = str_replace("Romania", "România", $item->country);
        $item->city    = str_replace("Cluj Napoca", "Cluj-Napoca", $item->city);
        $item->city    = str_replace("Bucharest", "București", $item->city);
        $item->city    = str_replace("Bucuresti", "București", $item->city);
        $item->id = md5($item->job_link) . "";
        $company = $item->company;
    }


    $data = json_encode($json);

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );

    $msg = $company . ' user: ' . get_user_from_api_key($key);
    discord_webhook($msg);
    $context  = stream_context_create($options);

    $url = 'http://' . $server . '/solr/' . $core . $command . $qs;

    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */
    }

    var_dump($result);
}


// endpoint starts here

foreach (getallheaders() as $name => $value) {
    if (($name == 'apikey')) {
        if (validate_api_key($value) == true) {

            update($value);
        } else {
            echo "apikey error";
        }
    }
}
