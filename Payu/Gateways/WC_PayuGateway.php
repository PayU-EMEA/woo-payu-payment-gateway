<?php

namespace Payu\PaymentGateway\Gateways;

interface WC_PayuGateway {
	public function is_enabled(): bool;
	public function get_terms_links(): array;
	public function get_payu_method_title(): string;
	public function get_payu_method_description(): string;
	public function get_payu_method_icon(): string;
	public function get_additional_data(): array;
}
