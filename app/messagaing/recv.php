<?php
require_once __DIR__ . '/libs/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
// create connection

$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' );
$channel = $connection->channel ();
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' ** Waiting for messages. To exit press CTRL+C **', "\n";


$callback = function ($msg) {
    //$message_id = $msg->get('correlation_id');
   $message = explode(", ", $msg->body);

    $get_ID = false;
    $create_invoice = false;
    $get_clients = false;
    $get_invoices = false;
    $email_invoice = false;
    echo "Nachrichtenteyp: ", $message[0], "\n";

    if(strcmp($message[0], 'neu')==0){
        create_client($message);
    }elseif(strcmp($message[0], 'rechnung')==0){
        create_invoice($message);
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


function create_client($msg) {
    echo '*************** create_client() aufgerufen ************', "\r\n";
    //-X POST localhost/api/v1/clients                          ==> die Methode
    // -H "Content-Type:application/json"                           ==> Header
    // -d '{"name":"Client","contact":{"email":"test@gmail.com"}}'  ==> Parameter der Methode
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"         ==> extra Header

    /*"create Firmenname2 "
	+ "Vorname2 Nachname2 Mail@mail.com Telefon2 "
	+ "Strasse2 Stadt2 Bundesland2 PLZ2 Land2" */

    $data = array(
        'name' => $msg[1],
        'contact' => array(
            'first_name' => $msg[2],
            'last_name' =>$msg[3],
            'email' => $msg[4],
            'phone'=>$msg[5]
        ),
        'address1'=>$msg[6],
        'city'=>$msg[7],
        'state' =>$msg[8],
        'postal_code'=>$msg[9],
        'country'=>$msg[10]

    );

    $client_url = 'localhost/api/v1/clients';
    $ch = curl_init($client_url);



    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: urT0RJsvMDv3GiHIQqNHF6ej3VzVbWk1'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_POST, true);
    $output=curl_exec($ch);
    curl_close($ch);

    echo '*************** create_client() ende ************', "\r\n";
}


function create_invoice($message) {
/*
    curl -X POST ninja.dev/api/v1/invoices
    -H "Content-Type:application/json"
    -d '{"client_id":"16", "product_key":"001"}'
    -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"
    */
    //("companyName", "itemNr", "product", "price", "quantity")
    echo "***************** create_invoice() aufgerufen ********", "\r\n";
    $id = get_ID($message[1]);
    echo "id: ", $id, "\r\n";
    $data = array(
        "client_id" => $id,
        "product_key" => $message[2],
        "notes" => $message[3],
        "cost" => $message[4],
        "qty" => $message[5]
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
    echo "***************** create_invoice() ende ********", "\r\n";
}


function get_ID($company_name){
    $client_id = 0;
        $clients = get_clients();
        echo '********** get_ID() aufgerufen ***********', "\r\n";

        $data = explode("},",$clients);
      for($i = 0; $i<count($data);$i++) {
          $client = explode(": [", $data[$i]);
          $a = explode(": ", $client[1]);

          $f_name = explode('"', $a[6]);
  //       $l_name = explode('"', $a[7]);
  //       $mail = explode('"', $a[8]);
          $id = explode('"', $a[11]);

          echo "\n";
          echo $f_name[1], "\n"; //Firmenname
       //   echo $l_name[1], "\n"; // nachname von Kontaktperson
       //   echo $mail[1], "\n"; // Email der Kontaktperson
          echo $id[1], "\n";
          echo "\n", '**************************************************', "\n";

          echo strcmp($f_name[1], $company_name),  "\n";


          if (strcmp($f_name[1], $company_name) ==0) {
              echo "client_id gefunden. ", "\n";
              $client_id = $id[1];
              echo $client_id;
              break;
          }
      }
    echo '********** get_ID() ende ***********', "\r\n";
    return $client_id;
}

function get_clients(){

    // curl -X GET localhost/api/v1/clients
    // -H "X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6"
    echo '*************** get_clients() aufgerufen ************', "\r\n";
    $client_url = 'localhost/api/v1/clients';
    $ch = curl_init($client_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Ninja-Token: GuTtJU276mbWvAQnpFrw0ylvkRkaq6H6'));
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $output=curl_exec($ch);
    curl_close($ch);
    echo '***************get_clients() ende************', "\r\n";
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
        "id" => '21'
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
    $result = file_get_contents('http://localhost/api/v1/email_invoice', false, $context);

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