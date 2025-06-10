<?php

namespace Payu\PaymentGateway\Gateways;

interface WC_PayuCreditGateway {
	public function get_related_paytypes(): array;
}
