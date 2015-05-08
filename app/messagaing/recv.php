<?php
require_once __DIR__ . '/libs/vendor/autoload.php';
//require '/var/www/ninja/public/index.php';
require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/../public/index.php';
//require '/var/www/ninja/bootstrap/autoload.php';
//require '/var/www/ninja/bootstrap/app.php';

use PhpAmqpLib\Connection\AMQPConnection;
use App\Ninja\Repositories\ClientRepository as ClientRepo;
use App\Ninja\Repositories\AccountRepository as AccountRepo;

// create connection

$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' );
$channel = $connection->channel ();
$channel->queue_declare ( 'invoice', false, false, false, false );
echo ' ** Waiting for messages. To exit press CTRL+C **', "\n";

// wait for messages
$callback = function ($msg) {
/* createClient
    $clientRepo = new ClientRepo();
    $data = explode(" ", $msg->body);
    $data = createClientArray($data);
    $clientRepo->save(null, $data);

*/
    $accountRepo = new AccountRepo();
    $accountRepo->create("ninja", "ninja", "jeremias.twele@haw-hamburg.de", "ninja");

};

$channel->basic_consume ( 'invoice', '', false, true, false, false, $callback );

while ( count ( $channel->callbacks ) ) {
    $channel->wait ();
}

// close connection
$channel->close ();
$connection->close ();


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
