<?php

namespace Payu\PaymentGateway\Blocks;

class PayuApplePayBlock extends PayuBlocks {
	protected $name = 'payuapplepay';

	public function __construct() {
		parent::__construct(true);
	}
}
