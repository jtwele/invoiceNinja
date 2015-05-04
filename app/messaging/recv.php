<?php

namespace messaging;

require_once __DIR__ . '/libs/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use ninja\repositories\ClientRepository;


	// Default werte:
	// Username: guest
	// password: guest
	//
	// Änderung (in der Konsole eingeben):
	// rabbitmqctl delete_user guest
	// rabbitmqctl add_user ninja steffens => bedeutet, dass username = ninja und pw = steffens
	//
	//
	// create connection

		echo ' [*] RECV geladen';
		$clientRepo;
		echo 'null check';
		if (is_null (clientRepo )) {
			echo 'repo wird erstellt';
			$clientRepo = new ClientRepository ();
			echo '$clientRepo geladen !!' ;
		}

		$connection = new AMQPConnection ( '141.22.29.97', '5672', 'invoice', 'invoice' ); // host = host auf dem der Broker läuft
		$channel = $connection->channel ();
		echo 'Verbindung aufgebaut';
		// declaer messagequeue
		$channel->queue_declare ( 'invoice', false, false, false, false );		
		echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
		
		// wait for messages
		$callback = function ($msg) {
			$data = explode ( " ", $msg );
			$data = $this->createClientArrray ( $data );
			$this->clientRepo->save ( null, $data, null );
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
///////////////////////////////////////////////////////////////////////
				'contact' >= $data ( 8 ),
				'email' >= $data ( 9 ),
				'first_name' >= $data ( 10 ),
				'last_name' >= $data ( 11 ),
				'phone' >= $data ( 12 ) 
		);
		return $clientData;
	}

?>

