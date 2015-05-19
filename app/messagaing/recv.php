<?php
require_once __DIR__ . '/libs/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
// create connection

$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' );
$channel = $connection->channel ();
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' ** Waiting for messages. To exit press CTRL+C **', "\n";


$callback = function ($msg) {
    $createClient = true;
    if($createClient){
	echo 'if bestanen', "\n";
        get_clients();
  }
};

$channel->basic_consume ( 'invoice', '', false, true, false, false, $callback );

while ( count ( $channel->callbacks ) ) {
    $channel->wait ();
}

// close connection
$channel->close ();
$connection->close ();


function createClient() {

    //-X POST localhost/api/v1/clients                              ==> die Methode
    // -H "Content-Type:application/json"                           ==> Header
    // -d '{"name":"Client","contact":{"email":"test@gmail.com"}}'  ==> Parameter der Methode
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"         ==> extra Header


    echo'1. createClient wurde aufgerufen', "\n";
    $client_url = 'localhost/api/v1/clients';
    $ch = curl_init($client_url);
    echo'2. createClient() url initialisiert', "\n";
    $curl_post_data = array(
        "name" => 'Testname',
        "contact"=> array(
            "email" => 'testname@example.com'
        )
    );
    echo'3. createClient() Array mit Kundendaten angelegt', "\n";

    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    echo'4. createClient() erster header gesetzt ', "\n";
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    echo'5. createClient() zweiter header gesetzt', "\n";
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
    echo'6. createClient() parameter gesetzt', "\n";
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPGET, false);
    echo'7. createClient() fuehre curl Befehl durch', "\n";
    $output=curl_exec($ch);
    echo'1. createClient() curl befehl durchgefuehrt', "\n";
    curl_close($ch);
}


function create_invoice() {

    //curl -X POST localhost/api/v1/invoices
    // -H "Content-Type:application/json"
    // -d '{"client_id":"1", "product_key":"ITEM"}'
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"

    echo'1. create_invoice wurde aufgerufen', "\n";
    $invoice_url = 'localhost/api/v1/invoices';
    $ch = curl_init($invoice_url);
    echo'2. create_invoice() url initialisiert', "\n";
    $curl_post_data = array(
        "client_id" => '1',
        "product_key" => '' //TODO: was ist ein product_key?
    );
    echo'3. create_invoice() Array mit Kundendaten angelegt', "\n";

    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    echo'4. create_invoice() erster header gesetzt ', "\n";
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    echo'5. create_invoice() zweiter header gesetzt', "\n";
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
    echo'6. create_invoice() parameter gesetzt', "\n";
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPGET, false);
    echo'7. create_invoice() fuehre curl Befehl durch', "\n";
    curl_exec($ch);
    echo'1. create_invoice() curl befehl durchgefuehrt', "\n";
    curl_close($ch);
}


function get_clients(){

    // curl -X GET localhost/api/v1/clients
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"

    $client_url = 'localhost/api/v1/clients';
    $ch = curl_init($client_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    curl_setopt($ch, CURLOPT_POST, fasle);
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
    curl_setopt($ch, CURLOPT_POST, fasle);
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
