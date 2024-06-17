<?php

namespace Payu\PaymentGateway\Gateways;

interface WC_PayuGateway {
	public function is_enabled(): bool;
	public function get_payu_method_title(): string;
	public function get_payu_method_description(): string;
	public function get_payu_method_icon(): string;
	public function is_payu_show_terms_info(): bool;
}
