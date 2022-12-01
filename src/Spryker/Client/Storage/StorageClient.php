<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Storage;

use Generated\Shared\Transfer\StorageScanResultTransfer;
use Spryker\Client\Kernel\AbstractClient;
use Spryker\Client\Storage\Exception\InvalidStorageScanPluginInterfaceException;
use Spryker\Client\Storage\Redis\Service;
use Spryker\Client\StorageExtension\Dependency\Plugin\StorageScanPluginInterface;
use Spryker\Shared\Storage\StorageConstants;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Client\Storage\StorageFactory getFactory()
 */
class StorageClient extends AbstractClient implements StorageClientInterface
{
    /**
     * @var string
     */
    public const KEY_NAME_PREFIX = 'storage';

    /**
     * @var string
     */
    public const KEY_NAME_SEPARATOR = ':';

    /**
     * @var string
     */
    public const KEY_USED = 'used';

    /**
     * @var string
     */
    public const KEY_NEW = 'new';

    /**
     * @var string
     */
    public const KEY_INIT = 'init';

    /**
     * @var string
     */
    protected const CACHE_KEY_PREFIX = 'cache';

    /**
     * All keys which have been used for the last request with same URL
     *
     * @var array|null
     */
    public static $cachedKeys;

    /**
     * Pre-loaded values for this URL from Storage
     *
     * @var array|null
     */
    protected static $bufferedValues;

    /**
     * The Buffer for already decoded buffered values
     *
     * @var array|null
     */
    protected static $bufferedDecodedValues;

    /**
     * @var \Spryker\Client\Storage\Redis\ServiceInterface|\Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface|\Spryker\Client\StorageExtension\Dependency\Plugin\StorageScanPluginInterface|null
     */
    public static $service;

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return \Spryker\Client\Storage\Redis\ServiceInterface|\Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface|\Spryker\Client\StorageExtension\Dependency\Plugin\StorageScanPluginInterface
     */
    public function getService()
    {
        if (static::$service === null) {
            static::$service = $this->getFactory()->createCachedService();
        }

        return static::$service;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array|null
     */
    public function getCachedKeys()
    {
        return static::$cachedKeys;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array|null $keys
     *
     * @return array|null
     */
    public function setCachedKeys($keys)
    {
        return static::$cachedKeys = $keys;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return void
     */
    public function resetCache()
    {
        static::$cachedKeys = null;
        static::$bufferedValues = null;
        static::$bufferedDecodedValues = null;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     *
     * @return void
     */
    public function set($key, $value, $ttl = null)
    {
        $this->getService()->set($key, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array $items
     *
     * @return void
     */
    public function setMulti(array $items)
    {
        $this->getService()->setMulti($items);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $key
     *
     * @return void
     */
    public function unsetCachedKey($key)
    {
        unset(static::$cachedKeys[$key]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return void
     */
    public function unsetLastCachedKey()
    {
        array_pop(static::$cachedKeys);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $this->getService()->delete($key);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array $keys
     *
     * @return void
     */
    public function deleteMulti(array $keys)
    {
        $this->getService()->deleteMulti($keys);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return int
     */
    public function deleteAll()
    {
        return $this->getService()->deleteAll();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $this->loadCacheKeysAndValues();

        if (!array_key_exists($key, static::$bufferedValues)) {
            static::$cachedKeys[$key] = static::KEY_NEW;

            return $this->getService()->get($key);
        }

        static::$cachedKeys[$key] = static::KEY_USED;

        if (!array_key_exists($key, static::$bufferedDecodedValues)) {
            static::$bufferedDecodedValues[$key] = $this->jsonDecode(static::$bufferedValues[$key]);
        }

        return static::$bufferedDecodedValues[$key];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $keys
     *
     * @return array
     */
    public function getMulti(array $keys)
    {
        $this->loadCacheKeysAndValues();

        // Get immediately available values
        $keyValues = array_intersect_key(static::$bufferedValues, array_flip($keys));

        foreach ($keyValues as $key => $keyValue) {
            static::$cachedKeys[$key] = static::KEY_USED;
        }

        $allPreparedKeys = $this->prefixKeyValues(array_flip($keys));

        // Get the rest of requested keys without a value
        $keys = array_diff($keys, array_keys($keyValues));

        $keyValues = $this->prefixKeyValues($keyValues);

        if ($keys) {
            $keyValues += $this->getService()->getMulti($keys);
            static::$cachedKeys += array_fill_keys($keys, static::KEY_NEW);
        }

        return array_merge($allPreparedKeys, $keyValues);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array
     */
    public function getStats()
    {
        return $this->getService()->getStats();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array
     */
    public function getAllKeys()
    {
        return $this->getService()->getAllKeys();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return void
     */
    public function resetAccessStats()
    {
        $this->getService()->resetAccessStats();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array
     */
    public function getAccessStats()
    {
        return $this->getService()->getAccessStats();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return int
     */
    public function getCountItems()
    {
        return $this->getService()->getCountItems();
    }

    /**
     * @return void
     */
    protected function loadCacheKeysAndValues()
    {
        if (static::$cachedKeys === null) {
            $this->loadKeysFromCache();
        }

        if (static::$bufferedValues === null) {
            $this->loadAllValues();
        }
    }

    /**
     * @return void
     */
    protected function loadKeysFromCache()
    {
        static::$cachedKeys = [];
        $cacheKey = $this->buildCacheKey();

        if (!$cacheKey) {
            return;
        }

        $cachedKeys = $this->getService()->get($cacheKey);

        if ($cachedKeys && is_array($cachedKeys)) {
            foreach ($cachedKeys as $key) {
                static::$cachedKeys[$key] = static::KEY_INIT;
            }
        }
    }

    /**
     * @param array $keyValues
     *
     * @return array
     */
    protected function prefixKeyValues(array $keyValues)
    {
        $prefixedKeyValues = [];

        foreach ($keyValues as $key => $value) {
            $prefixedKeyValues[$this->getKeyPrefix() . $key] = $value;
        }

        return $prefixedKeyValues;
    }

    /**
     * Pre-Loads all values from storage with mget()
     *
     * @return void
     */
    protected function loadAllValues()
    {
        static::$bufferedValues = [];
        static::$bufferedDecodedValues = [];

        if (static::$cachedKeys && is_array(static::$cachedKeys)) {
            $values = $this->getService()->getMulti(array_keys(static::$cachedKeys));

            if ($values && is_array($values)) {
                foreach ($values as $key => $value) {
                    $keySuffix = substr($key, strlen($this->getKeyPrefix()));
                    static::$bufferedValues[$keySuffix] = $value;
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getKeyPrefix()
    {
        return Service::KV_PREFIX;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $pattern
     *
     * @return array
     */
    public function getKeys($pattern = '*')
    {
        return $this->getService()->getKeys($pattern);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $pattern
     * @param int $limit
     * @param int|null $cursor
     *
     * @throws \Spryker\Client\Storage\Exception\InvalidStorageScanPluginInterfaceException
     *
     * @return \Generated\Shared\Transfer\StorageScanResultTransfer
     */
    public function scanKeys(string $pattern, int $limit, ?int $cursor = 0): StorageScanResultTransfer
    {
        if (!$this->getService() instanceof StorageScanPluginInterface) {
            throw new InvalidStorageScanPluginInterfaceException(
                'In order to use the method `scanKeys` you need a service that implements the plugin interface `StorageScanPluginInterface`',
            );
        }

        return $this->getService()->scanKeys($pattern, $limit, $cursor);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $storageCacheStrategyName
     *
     * @return void
     */
    public function persistCacheForRequest(Request $request, $storageCacheStrategyName = StorageConstants::STORAGE_CACHE_STRATEGY_REPLACE)
    {
        $cacheKey = $this->buildCacheKey($request);

        if ($cacheKey && is_array(static::$cachedKeys)) {
            $this->updateCache($storageCacheStrategyName, $cacheKey);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @deprecated Use {@link persistCacheForRequest()} instead.
     *
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return void
     */
    public static function persistCache(?Request $request = null)
    {
        $cacheKey = static::generateCacheKey($request);

        if ($cacheKey && is_array(static::$cachedKeys)) {
            $updateCache = false;
            foreach (static::$cachedKeys as $key => $status) {
                if ($status === static::KEY_INIT) {
                    unset(static::$cachedKeys[$key]);
                }

                if ($status !== static::KEY_USED) {
                    $updateCache = true;
                }
            }

            if ($updateCache) {
                $ttl = static::getFactory()
                    ->getStorageClientConfig()
                    ->getStorageCacheTtl();

                static::$service->set($cacheKey, json_encode(array_keys(static::$cachedKeys)), $ttl);
            }
        }
    }

    /**
     * This method exists for BC reasons only and should be removed with next major release.
     *
     * @deprecated Use {@link \Spryker\Client\Storage\StorageClient::buildCacheKey()} instead.
     *
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return string
     */
    protected static function generateCacheKey(?Request $request = null): string
    {
        return (new static())->getFactory()->createCacheKeyGenerator()->generateCacheKey($request);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return string
     */
    protected function buildCacheKey(?Request $request = null): string
    {
        return $this->getFactory()->createCacheKeyGenerator()->generateCacheKey($request);
    }

    /**
     * @param string $storageCacheStrategyName
     * @param string $cacheKey
     *
     * @return void
     */
    protected function updateCache($storageCacheStrategyName, $cacheKey): void
    {
        $this->getFactory()
            ->createStorageCacheStrategy($storageCacheStrategyName)
            ->updateCache($cacheKey);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function jsonDecode($value)
    {
        $result = json_decode((string)$value, true);

        if (json_last_error() === JSON_ERROR_SYNTAX) {
            return $value;
        }

        return $result;
    }
}
