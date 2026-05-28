<?php

namespace Payu\PaymentGateway\Cache;
use OauthCacheInterface;

class OauthCache implements OauthCacheInterface {
	public function get( string $key ): ?\OauthResultClientCredentials
	{
		$cache = get_transient( $key );

		return $cache === false ? null : unserialize(
			$cache,
			['allowed_classes' => [\OauthResultClientCredentials::class, \DateTime::class]]
		);
	}

	public function set( string $key, \OauthResultClientCredentials $value ): bool
	{
		return set_transient( $key, serialize( $value ) );
	}

	public function invalidate( string $key ): bool {
		return delete_transient( $key );
	}
}
