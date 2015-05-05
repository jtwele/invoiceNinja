<?php
require_once __DIR__ . '/libs/vendor/autoload.php';
require_once '/var/www/ninja/bootstrap/autoload.php';

//foreach (glob("/var/www/ninja/vendor/composer/*.php") as $filename)
//{
//    require_once $filename;
//}
use PhpAmqpLib\Connection\AMQPConnection;
use App\Ninja\Repositories\ClientRepository as Repo;

// create connection

$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' );
$channel = $connection->channel ();
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' ** Waiting for messages. To exit press CTRL+C **', "\n";

// wait for messages
$callback = function ($msg) {

    $clientRepo = new Repo();
    $data = explode(" ", $msg->body);
    echo '***********************************************';
    $data = createClientArray($data);
    echo var_dump($data);
    echo '***********************************************';
    $clientRepo->save(null, $data);
};

$channel->basic_consume ( 'invoice', '', false, true, false, false, $callback );

while ( count ( $channel->callbacks ) ) {
    $channel->wait ();
}

// close connection
$channel->close ();
$connection->close ();
/*
function getRepoInstance(ClientRepository $clientRepo){

    if(is_null($clientRepo)){
        echo'3.1 ClientRepo muss erstellt werden!!!';
        $clientRepo = new ClientRepository();
        echo'3.2 ClientRepo wurde erstellt!!!';
    }

    return repo;
}
*/


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
