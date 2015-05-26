<?php
require_once __DIR__ . '/libs/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
// create connection

$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' );
$channel = $connection->channel ();
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' ** Waiting for messages. To exit press CTRL+C **', "\n";


$callback = function ($msg) {

    $find_client = true;

    $create_client = false;
    $create_invoice = false;
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
    }elseif($find_client) {
        find_client();
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
        'address1'=>'test Street',
        'city'=>'Hamburg',
        'state' =>'Hamburg',
        'postal_code'=>'20144',
        'country'=>'germany'

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
/*
    curl -X POST ninja.dev/api/v1/invoices
    -H "Content-Type:application/json"
    -d '{"client_id":"16", "product_key":"001"}'
    -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"
    */
    $data = array(
        "client_id" => '16',
        "product_key" => '1234',
        "notes" => 'ersteRechnung mit PHP erstellt',
        "cost" => '50.00',
        "qty" => '3'
    );

    $data_string = json_encode($data);

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type:application/json\r\n" . "Content-Length: " .
                strlen($data_string) .
                "\r\n"."X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6\r\n",
            'content' => $data_string
        )
    ));

    $result = file_get_contents('http://localhost/api/v1/invoices', false, $context);
}


function find_client($name, $last_name, $email){
    $clients = get_clients();
        echo "\n", '**************************************************', "\n";
        $data = explode("},",$clients);
      for($i = 0; $i<count($data);$i++){
          echo "\n", '**************************************************';
          $client = explode(": [", $data[$i]);
          $a = explode(": ", $client[1]);

          $f_name = explode('"', $a[6]);

          //$first_name = explode(",", $f_name[0]);


         for($j = 0;$j< count($f_name); $j++){
             echo $j, ": ";
              echo $f_name[$j], "\n";
          }



         // $l_name = explode(",", $a[7]);
         // $mail = explode(",", $a[8]);
          echo "\n", '**************************************************', "\n";
          /*
          if($f_name[0] == $name &&
             $l_name[0] == $last_name &&
             $email == $mail[0]){


          }
          */
          echo "\n", '**************************************************', "\n";
/*
          if($a == $name && $a == $last_name && $a == $email){

          }
*/
          echo "\n", '**************************************************';
      }
    //:TODO client finden impln
}

function get_clients(){

    // curl -X GET localhost/api/v1/clients
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"

    $client_url = 'localhost/api/v1/clients';
    $ch = curl_init($client_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $output=curl_exec($ch);
    curl_close($ch);

    return $output;
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

function email_invoice(){

    /*
     * curl -X POST ninja.dev/api/v1/email_invoice
     * -H "Content-Type:application/json"
     * -d '{"id":1}'
     * -H "X-Ninja-Token: TOKEN"
     */
    $data = array(
        "id" => 'XXX' //TODO: ID angeben;
    );

    $data_string = json_encode($data);

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Content-Type:application/json\r\n" . "Content-Length: " .
                strlen($data_string) .
                "\r\n"."X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6\r\n",
            'content' => $data_string
        )
    ));

    $result = file_get_contents('ninja.dev/api/v1/email_invoice', false, $context);


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
