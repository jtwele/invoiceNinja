 <?php
	require_once __DIR__ . '/libs/vendor/autoload.php';
	use PhpAmqpLib\Connection\AMQPConnection;
use App\Ninja\Repositories\ClientRepository;
	// Username: guest
	// password: guest
	//
	// €nderung (in der Konsole eingeben):
	// rabbitmqctl delete_user guest
	// rabbitmqctl add_user ninja steffens => bedeutet, dass username = ninja und pw = steffens
	//
	//
	// create connection
	echo'1. Datei geladen!!!';
	$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' ); // host = host auf dem der Broker lŠuft
	$channel = $connection->channel ();
	echo'2. Channel erstellt!!!';
	
	$clientRepo;
	echo'3. ClientRepo wird geprŸft!!!';
	if($clientRepo = null){
		echo'3.1 ClientRepo muss erstellt werden!!!';
		$clientRepo = new ClientRepository();
		echo'3.2 ClientRepo wurde erstellt!!!';
	}
	echo'4. declare messagequeue !!!';
	$channel->queue_declare ( 'invoice', false, false, false, false );
	
	echo ' 5. Waiting for messages. To exit press CTRL+C', "\n";
	
	// wait for messages
	$callback = function ($msg) {
		$clientRepo;
		if($clientRepo = null){
			$clientRepo = new ClientRepository();
		}
		$data = explode(" ", $msg);
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