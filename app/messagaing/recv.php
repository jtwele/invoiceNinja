<?php
require_once __DIR__ . '/libs/vendor/autoload.php';
require_once('/var/www/ninja/app/Ninja/Repositories/ClientRepository.php');
use PhpAmqpLib\Connection\AMQPConnection;
use App\Ninja\Repositories\ClientRepository as Repo;

// create connection
echo'1. Datei geladen!!!';
$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' ); // host = host auf dem der Broker l�uft
$channel = $connection->channel ();
echo'2. Channel erstellt!!!';
echo'4. declare messagequeue !!!';
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' 5. Waiting for messages. To exit press CTRL+C', "\n";



// wait for messages
$callback = function ($msg) {
    $clientRepo = new Repo();
    $tmpMsg = strval($msg);
    $data = explode(" ", $tmpMsg);
    for($i=0; $i < count($data); $i++)
    {
        echo $data[$i]."<br>";
    }


    $clientRepo->save(null, $data);
    echo " [x] Received ", $msg->body, "\n";
};

$channel->basic_consume ( 'invoice', '', false, true, false, false, $callback );

while ( count ( $channel->callbacks ) ) {
    $channel->wait ();
}

// close connection
$channel->close ();
$connection->close ();

function getRepoInstance(){
    //ClientRepository $clientRepo;
    if(is_null($clientRepo)){
        echo'3.1 ClientRepo muss erstellt werden!!!';
        $clientRepo = new ClientRepository();
        echo'3.2 ClientRepo wurde erstellt!!!';
    }

    return repo;
}

function createClientArrray($data) {
    $clientData = array (
        'name' >= $data ( 0 ),
        'id_number' >= $data ( 1 ),
        'work_phone' >= $data ( 2 ),
        'address1' >= $data ( 3 ),
        'city' >= $data ( 4 ),
        'state' >= $data ( 5 ),
        'postal_code' >= $data ( 6 ),
        'country_id' >= $data ( 7 ),

        'contact' >= $data ( 8 ),
        'email' >= $data ( 9 ),
        'first_name' >= $data ( 10 ),
        'last_name' >= $data ( 11 ),
        'phone' >= $data ( 12 )
    );
    return $clientData;
}

?>
