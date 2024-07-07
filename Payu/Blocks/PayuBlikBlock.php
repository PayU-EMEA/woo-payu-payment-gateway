<?php

namespace Payu\PaymentGateway\Blocks;

class PayuBlikBlock extends PayuBlocks
{
	protected $name = 'payublik';

	public function __construct() {
		parent::__construct(true);
	}
}
