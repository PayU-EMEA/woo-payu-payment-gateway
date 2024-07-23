<?php

namespace Payu\PaymentGateway\Blocks;

class PayuListBanksBlock extends PayuBlocks {
	protected $name = 'payulistbanks';

	public function __construct() {
		parent::__construct( true );
	}
}
