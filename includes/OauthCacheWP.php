<?php

class OauthCacheWP implements OauthCacheInterface
{
    public function get($key)
    {
        $cache = get_transient($key);
        return $cache === false ? null : unserialize($cache);
    }

    public function set($key, $value)
    {
        return set_transient($key, serialize($value));
    }

    public function invalidate($key)
    {
        return delete_transient($key);
    }
}
