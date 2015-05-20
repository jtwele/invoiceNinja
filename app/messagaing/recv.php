<?php
require_once __DIR__ . '/libs/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
// create connection

$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' );
$channel = $connection->channel ();
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' ** Waiting for messages. To exit press CTRL+C **', "\n";


$callback = function ($msg) {
    $create_client = false;
    $create_invoice = true;
    $get_clients = false;
    $get_invoices = false;

    if($create_client){
        create_client();
    }elseif($get_clients){
        get_clients();
    }elseif($create_invoice){
        create_invoice();
    }elseif($get_invoices){
        get_invoices();
    }else{
        echo 'unbekannter Befehl';
    }
};

$channel->basic_consume ( 'invoice', '', false, true, false, false, $callback );

while ( count ( $channel->callbacks ) ) {
    $channel->wait ();
}

// close connection
$channel->close ();
$connection->close ();


function create_client() {

    //-X POST localhost/api/v1/clients                              ==> die Methode
    // -H "Content-Type:application/json"                           ==> Header
    // -d '{"name":"Client","contact":{"email":"test@gmail.com"}}'  ==> Parameter der Methode
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"         ==> extra Header
    $data = array(
        'name' => 'nameInPHPerstellt',
        'contact' => array(
            'first_name' =>'Jeremias',
            'last_name' =>'Twele',
            'email' => 'jeremias.twele@haw-hamburg.de',
            'phone'=>'04012345678'
        ),
        'adress' => array(
            'street'=>'test Street',
            'city'=>'Hamburg',
            'postal_code'=>'20144',
            'country'=>'germany'
        )
    );

    $data_string = json_encode($data);

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" . "Content-Length: " .
                strlen($data_string) .
                "\r\n"."X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6\r\n",
        'content' => $data_string
    )
));

$result = file_get_contents('http://localhost/api/v1/clients', false, $context);

}


function create_invoice() {

    //curl -X POST localhost/api/v1/invoices
    // -H "Content-Type:application/json"
    // -d '{"client_id":"1", "product_key":"ITEM"}'
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"

    $invoice_url = 'localhost/api/v1/invoices';
    $ch = curl_init($invoice_url);
    $data = array(
        "client_id" => '16',
        "product_key" => '4321', //TODO: was ist ein product_key?
        "description" => 'ersteRechnung mit PHP erstellt',
        "unit_cost" => '50.00',
        "quantity" => '3'
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_exec($ch);
    curl_close($ch);

}


function get_clients(){

    // curl -X GET localhost/api/v1/clients
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"

    $client_url = 'localhost/api/v1/clients';
    $ch = curl_init($client_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $output=curl_exec($ch);
    curl_close($ch);

}



function get_invoices(){

    // curl -X GET localhost/api/v1/invoices
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"

    $invoice_url = 'localhost/api/v1/invoices';
    $ch = curl_init($invoice_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $output=curl_exec($ch);
    curl_close($ch);

}

function createClientArray($data) {

    $clientData = array (
        'name' => $data [0],
        'id_number' => $data[1],
        'work_phone' => $data [2],
        'address1' => $data[3],
        'city' => $data [4],
        'state' => $data [5],
        'postal_code' => $data [6],
        'country_id' => [7],

        'contact' => $data[8],
        'email' => $data [9],
        'first_name' => $data [10],
        'last_name' => $data [11],
        'phone' => $data [12]
    );
    return $clientData;
}

?>
