<?php
require_once __DIR__ . '/libs/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
class Messaging {
	
	
	function send($nachricht) {
		$connection = new AMQPConnection ( '141.22.29.97', 5672, 'invoiceSender', 'invoiceSender' );
		$channel = $connection->channel ();
		$channel->queue_declare ( 'controllerInvoice', false, false, false, false );
		
		settype ( $nachricht, "string" );
		$msg = new AMQPMessage ( $nachricht );
		
		$channel->basic_publish ( $msg, '', 'controllerInvoice' );

		$channel->close ();
		$connection->close ();
	}
}

?>
