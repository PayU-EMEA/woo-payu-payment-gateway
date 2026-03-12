<?php

namespace Payu\PaymentGateway\Blocks;

class PayuGooglePayBlock extends PayuBlocks {
	protected $name = 'payugooglepay';

	public function __construct() {
		parent::__construct(true);
	}
}
