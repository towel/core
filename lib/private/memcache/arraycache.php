<?php
/**
 * Copyright (c) 2015 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class ArrayCache extends Cache {
	/** @var array Array with the cached data */
	protected $cachedData = array();

	/** @var array Array that keeps the TTL timestamps of the entries */
	protected $ttlData = array();

	/**
	 * {@inheritDoc}
	 */
	public function get($key) {
		if ($this->hasKey($key) && $this->isStillValid($key)) {
			return $this->cachedData[$key];
		}
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value, $ttl = 0) {
		$this->cachedData[$key] = $value;
		if ($ttl) {
			$this->ttlData[$key] = time() + $ttl;
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasKey($key) {
		return isset($this->cachedData[$key]) && $this->isStillValid($key);
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove($key) {
		unset($this->cachedData[$key]);
		unset($this->ttlData[$key]);
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear($prefix = '') {
		foreach ($this->cachedData as $key => $value) {
			if ($prefix === '' || strpos($key, $prefix) === 0) {
				$this->remove($key);
			}
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	static public function isAvailable() {
		return true;
	}

	/**
	 * Checks whether the TTL of an entry is still valid or not
	 *
	 * @param string $key
	 * @return bool True if the entry has no TTL or TTL is not reached yet.
	 */
	protected function isStillValid($key) {
		return !isset($this->ttlData[$key]) || $this->ttlData[$key] >= time();
	}
}
