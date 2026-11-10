<?php
/**
 * Yablonsky Performance Object Cache Drop-in
 * Generated from an explicit YPerf Redis activation contract.
 * Owner: yablonsky-performance
 * Contract: __YPERF_CONTRACT__
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('YPERF_OBJECT_CACHE_REDIS_HOST')) {
    define('YPERF_OBJECT_CACHE_REDIS_HOST', '127.0.0.1');
}
if (!defined('YPERF_OBJECT_CACHE_REDIS_PORT')) {
    define('YPERF_OBJECT_CACHE_REDIS_PORT', 6379);
}
if (!defined('YPERF_OBJECT_CACHE_REDIS_DB')) {
    define('YPERF_OBJECT_CACHE_REDIS_DB', __YPERF_REDIS_DB__);
}
if (!defined('YPERF_OBJECT_CACHE_PREFIX')) {
    define('YPERF_OBJECT_CACHE_PREFIX', '__YPERF_PREFIX__');
}

if (!class_exists('WP_Object_Cache', false)) {
    class WP_Object_Cache
    {
        public $cache = array();
        public $cache_hits = 0;
        public $cache_misses = 0;

        private $redis = null;
        private $redis_ok = false;
        private $global_groups = array();
        private $non_persistent_groups = array();
        private $blog_prefix = '';
        private $generation = '1';

        public function __construct()
        {
            $this->blog_prefix = function_exists('is_multisite') && is_multisite() ? ((string) get_current_blog_id()) . ':' : '';
            $this->connect();
        }

        private function connect()
        {
            if ($this->payload_auth_key() === '' || !class_exists('Redis')) {
                return;
            }
            try {
                $redis = new Redis();
                if (!$redis->connect(YPERF_OBJECT_CACHE_REDIS_HOST, (int) YPERF_OBJECT_CACHE_REDIS_PORT, 0.25)) {
                    return;
                }
                if (!$redis->select((int) YPERF_OBJECT_CACHE_REDIS_DB)) {
                    $redis->close();
                    return;
                }
                $this->redis = $redis;
                $this->redis_ok = true;
                $generation_key = YPERF_OBJECT_CACHE_PREFIX . '__generation';
                $current = $redis->get($generation_key);
                if ($current === false) {
                    $redis->setnx($generation_key, '1');
                    $current = $redis->get($generation_key);
                }
                $this->generation = is_scalar($current) && (string) $current !== '' ? (string) $current : '1';
            } catch (Throwable $throwable) {
                $this->redis = null;
                $this->redis_ok = false;
            }
        }

        private function normalized_group($group)
        {
            $group = (string) $group;
            return $group !== '' ? $group : 'default';
        }

        private function is_non_persistent($group)
        {
            return isset($this->non_persistent_groups[$this->normalized_group($group)]);
        }

        private function storage_key($key, $group)
        {
            $group = $this->normalized_group($group);
            $scope = isset($this->global_groups[$group]) ? 'global:' : $this->blog_prefix;
            return YPERF_OBJECT_CACHE_PREFIX . $this->generation . ':' . hash('sha256', $scope . $group . '|' . (string) $key);
        }

        private function payload_auth_key()
        {
            $parts = array();
            foreach (array('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY') as $constant_name) {
                if (!defined($constant_name)) {
                    continue;
                }
                $value = (string) constant($constant_name);
                if ($value === '' || stripos($value, 'put your unique phrase here') !== false) {
                    continue;
                }
                $parts[] = $constant_name . ':' . $value;
            }
            if ($parts === array()) {
                return '';
            }
            return hash('sha256', 'yperf-object-cache-v2|' . implode('|', $parts), true);
        }

        private function encode($value)
        {
            $key = $this->payload_auth_key();
            if ($key === '') {
                return false;
            }
            $encoded = base64_encode(serialize($value));
            $mac = hash_hmac('sha256', $encoded, $key);
            return 'YPERF2:' . $mac . ':' . $encoded;
        }

        private function decode($raw, &$ok)
        {
            $ok = false;
            $key = $this->payload_auth_key();
            if ($key === '' || !is_string($raw) || strpos($raw, 'YPERF2:') !== 0) {
                return false;
            }
            $parts = explode(':', $raw, 3);
            if (count($parts) !== 3 || $parts[0] !== 'YPERF2' || preg_match('/^[a-f0-9]{64}$/', $parts[1]) !== 1) {
                return false;
            }
            if (!hash_equals($parts[1], hash_hmac('sha256', $parts[2], $key))) {
                return false;
            }
            $serialized = base64_decode($parts[2], true);
            if (!is_string($serialized)) {
                return false;
            }
            try {
                $value = unserialize($serialized);
            } catch (Throwable $throwable) {
                return false;
            }
            $ok = true;
            return $value;
        }

        private function copy_value($value)
        {
            return is_object($value) ? clone $value : $value;
        }

        public function yperf_status()
        {
            return array(
                'redis_connected' => $this->redis_ok,
                'database' => (int) YPERF_OBJECT_CACHE_REDIS_DB,
                'prefix' => (string) YPERF_OBJECT_CACHE_PREFIX,
                'generation' => (string) $this->generation,
                'payload_format' => 'yperf2-hmac-sha256',
                'payload_authentication' => $this->payload_auth_key() !== '',
            );
        }

        public function add($key, $data, $group = 'default', $expire = 0)
        {
            if (function_exists('wp_suspend_cache_addition') && wp_suspend_cache_addition()) {
                return false;
            }
            $group = $this->normalized_group($group);
            $storage_key = $this->storage_key($key, $group);
            if ($this->is_non_persistent($group) || !$this->redis_ok) {
                if (array_key_exists($storage_key, $this->cache)) {
                    return false;
                }
                $this->cache[$storage_key] = $this->copy_value($data);
                return true;
            }
            try {
                $payload = $this->encode($data);
                if (!is_string($payload)) {
                    return false;
                }
                $added = (bool) $this->redis->setnx($storage_key, $payload);
                if ($added && (int) $expire > 0) {
                    $this->redis->expire($storage_key, (int) $expire);
                }
                if ($added) {
                    $this->cache[$storage_key] = $data;
                }
                return $added;
            } catch (Throwable $throwable) {
                return false;
            }
        }

        public function replace($key, $data, $group = 'default', $expire = 0)
        {
            $found = false;
            $this->get($key, $group, false, $found);
            if (!$found) {
                return false;
            }
            return $this->set($key, $data, $group, $expire);
        }

        public function set($key, $data, $group = 'default', $expire = 0)
        {
            $group = $this->normalized_group($group);
            $storage_key = $this->storage_key($key, $group);
            if ($this->is_non_persistent($group) || !$this->redis_ok) {
                $this->cache[$storage_key] = $this->copy_value($data);
                return true;
            }
            try {
                $payload = $this->encode($data);
                if (!is_string($payload)) {
                    unset($this->cache[$storage_key]);
                    return false;
                }
                $stored = (int) $expire > 0
                    ? (bool) $this->redis->setex($storage_key, (int) $expire, $payload)
                    : (bool) $this->redis->set($storage_key, $payload);
                if ($stored) {
                    $this->cache[$storage_key] = $this->copy_value($data);
                } else {
                    unset($this->cache[$storage_key]);
                }
                return $stored;
            } catch (Throwable $throwable) {
                unset($this->cache[$storage_key]);
                return false;
            }
        }

        public function get($key, $group = 'default', $force = false, &$found = null)
        {
            $group = $this->normalized_group($group);
            $storage_key = $this->storage_key($key, $group);
            if (!$force && array_key_exists($storage_key, $this->cache)) {
                $found = true;
                $this->cache_hits++;
                return $this->copy_value($this->cache[$storage_key]);
            }
            if ($this->is_non_persistent($group) || !$this->redis_ok) {
                $found = array_key_exists($storage_key, $this->cache);
                if ($found) {
                    $this->cache_hits++;
                    return $this->copy_value($this->cache[$storage_key]);
                }
                $this->cache_misses++;
                return false;
            }
            try {
                $raw = $this->redis->get($storage_key);
                if ($raw === false) {
                    $found = false;
                    $this->cache_misses++;
                    return false;
                }
                $decoded_ok = false;
                $value = $this->decode($raw, $decoded_ok);
                if (!$decoded_ok) {
                    $found = false;
                    $this->cache_misses++;
                    return false;
                }
                $this->cache[$storage_key] = $this->copy_value($value);
                $found = true;
                $this->cache_hits++;
                return $this->copy_value($value);
            } catch (Throwable $throwable) {
                $found = false;
                $this->cache_misses++;
                return false;
            }
        }

        public function delete($key, $group = 'default', $deprecated = false)
        {
            $group = $this->normalized_group($group);
            $storage_key = $this->storage_key($key, $group);
            $local_had = array_key_exists($storage_key, $this->cache);
            unset($this->cache[$storage_key]);
            if ($this->is_non_persistent($group) || !$this->redis_ok) {
                return $local_had;
            }
            try {
                return $this->redis->del($storage_key) > 0 || $local_had;
            } catch (Throwable $throwable) {
                return false;
            }
        }

        public function incr($key, $offset = 1, $group = 'default')
        {
            $found = false;
            $value = $this->get($key, $group, true, $found);
            if (!$found || !is_numeric($value)) {
                return false;
            }
            $value = max(0, (int) $value + (int) $offset);
            return $this->set($key, $value, $group) ? $value : false;
        }

        public function decr($key, $offset = 1, $group = 'default')
        {
            return $this->incr($key, -1 * (int) $offset, $group);
        }

        public function flush()
        {
            $this->cache = array();
            if (!$this->redis_ok) {
                return true;
            }
            try {
                $generation_key = YPERF_OBJECT_CACHE_PREFIX . '__generation';
                $next = $this->redis->incr($generation_key);
                $this->generation = is_numeric($next) ? (string) $next : (string) time();
                return true;
            } catch (Throwable $throwable) {
                return false;
            }
        }

        public function flush_runtime()
        {
            $this->cache = array();
            return true;
        }

        public function close()
        {
            if ($this->redis_ok && $this->redis instanceof Redis) {
                try {
                    $this->redis->close();
                } catch (Throwable $throwable) {
                }
            }
            return true;
        }

        public function switch_to_blog($blog_id)
        {
            $this->blog_prefix = function_exists('is_multisite') && is_multisite() ? ((string) (int) $blog_id) . ':' : '';
            $this->cache = array();
        }

        public function add_global_groups($groups)
        {
            foreach ((array) $groups as $group) {
                $this->global_groups[$this->normalized_group($group)] = true;
            }
        }

        public function add_non_persistent_groups($groups)
        {
            foreach ((array) $groups as $group) {
                $this->non_persistent_groups[$this->normalized_group($group)] = true;
            }
        }

        public function reset()
        {
            return $this->flush_runtime();
        }

        public function get_multiple($keys, $group = 'default', $force = false)
        {
            $values = array();
            foreach ((array) $keys as $key) {
                $found = false;
                $values[$key] = $this->get($key, $group, $force, $found);
            }
            return $values;
        }

        public function set_multiple($data, $group = 'default', $expire = 0)
        {
            $ok = true;
            foreach ((array) $data as $key => $value) {
                $ok = $this->set($key, $value, $group, $expire) && $ok;
            }
            return $ok;
        }

        public function add_multiple($data, $group = 'default', $expire = 0)
        {
            $results = array();
            foreach ((array) $data as $key => $value) {
                $results[$key] = $this->add($key, $value, $group, $expire);
            }
            return $results;
        }

        public function delete_multiple($keys, $group = 'default')
        {
            $results = array();
            foreach ((array) $keys as $key) {
                $results[$key] = $this->delete($key, $group);
            }
            return $results;
        }

        public function stats()
        {
        }
    }
}

if (!function_exists('wp_cache_init')) {
    function wp_cache_init()
    {
        $GLOBALS['wp_object_cache'] = new WP_Object_Cache();
    }
}
if (!function_exists('wp_cache_add')) {
    function wp_cache_add($key, $data, $group = '', $expire = 0) { global $wp_object_cache; return $wp_object_cache->add($key, $data, $group, $expire); }
}
if (!function_exists('wp_cache_replace')) {
    function wp_cache_replace($key, $data, $group = '', $expire = 0) { global $wp_object_cache; return $wp_object_cache->replace($key, $data, $group, $expire); }
}
if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0) { global $wp_object_cache; return $wp_object_cache->set($key, $data, $group, $expire); }
}
if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null) { global $wp_object_cache; return $wp_object_cache->get($key, $group, $force, $found); }
}
if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '') { global $wp_object_cache; return $wp_object_cache->delete($key, $group); }
}
if (!function_exists('wp_cache_incr')) {
    function wp_cache_incr($key, $offset = 1, $group = '') { global $wp_object_cache; return $wp_object_cache->incr($key, $offset, $group); }
}
if (!function_exists('wp_cache_decr')) {
    function wp_cache_decr($key, $offset = 1, $group = '') { global $wp_object_cache; return $wp_object_cache->decr($key, $offset, $group); }
}
if (!function_exists('wp_cache_flush')) {
    function wp_cache_flush() { global $wp_object_cache; return $wp_object_cache->flush(); }
}
if (!function_exists('wp_cache_close')) {
    function wp_cache_close() { global $wp_object_cache; return $wp_object_cache->close(); }
}
if (!function_exists('wp_cache_switch_to_blog')) {
    function wp_cache_switch_to_blog($blog_id) { global $wp_object_cache; return $wp_object_cache->switch_to_blog($blog_id); }
}
if (!function_exists('wp_cache_add_global_groups')) {
    function wp_cache_add_global_groups($groups) { global $wp_object_cache; $wp_object_cache->add_global_groups($groups); }
}
if (!function_exists('wp_cache_add_non_persistent_groups')) {
    function wp_cache_add_non_persistent_groups($groups) { global $wp_object_cache; $wp_object_cache->add_non_persistent_groups($groups); }
}
if (!function_exists('wp_cache_reset')) {
    function wp_cache_reset() { global $wp_object_cache; return $wp_object_cache->reset(); }
}
if (!function_exists('wp_cache_get_multiple')) {
    function wp_cache_get_multiple($keys, $group = '', $force = false) { global $wp_object_cache; return $wp_object_cache->get_multiple($keys, $group, $force); }
}
if (!function_exists('wp_cache_set_multiple')) {
    function wp_cache_set_multiple(array $data, $group = '', $expire = 0) { global $wp_object_cache; return $wp_object_cache->set_multiple($data, $group, $expire); }
}
if (!function_exists('wp_cache_add_multiple')) {
    function wp_cache_add_multiple(array $data, $group = '', $expire = 0) { global $wp_object_cache; return $wp_object_cache->add_multiple($data, $group, $expire); }
}
if (!function_exists('wp_cache_delete_multiple')) {
    function wp_cache_delete_multiple(array $keys, $group = '') { global $wp_object_cache; return $wp_object_cache->delete_multiple($keys, $group); }
}
if (!function_exists('wp_cache_flush_runtime')) {
    function wp_cache_flush_runtime() { global $wp_object_cache; return method_exists($wp_object_cache, 'flush_runtime') ? $wp_object_cache->flush_runtime() : false; }
}
if (!function_exists('yperf_object_cache_runtime_status')) {
    function yperf_object_cache_runtime_status()
    {
        global $wp_object_cache;
        return is_object($wp_object_cache) && method_exists($wp_object_cache, 'yperf_status')
            ? $wp_object_cache->yperf_status()
            : array('redis_connected' => false);
    }
}
if (!function_exists('wp_cache_supports')) {
    function wp_cache_supports($feature)
    {
        return in_array((string) $feature, array('get_multiple', 'set_multiple', 'add_multiple', 'delete_multiple', 'flush_runtime'), true);
    }
}
