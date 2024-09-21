<?php

namespace Payu\PaymentGateway\Blocks;

class PayuSecureFormBlock extends PayuBlocks {
	protected $name = 'payusecureform';

	public function __construct() {
		parent::__construct( true );
	}
}
