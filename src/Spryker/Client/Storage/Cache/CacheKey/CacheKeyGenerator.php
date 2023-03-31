<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Storage\Cache\CacheKey;

use Spryker\Client\Storage\Dependency\Client\StorageToLocaleClientInterface;
use Spryker\Client\Storage\Dependency\Client\StorageToStoreClientInterface;
use Spryker\Client\Storage\StorageConfig;
use Symfony\Component\HttpFoundation\Request;

class CacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /**
     * @var string
     */
    protected const KEY_NAME_PREFIX = 'storage';

    /**
     * @var string
     */
    protected const KEY_NAME_SEPARATOR = ':';

    /**
     * @var \Spryker\Client\Storage\Dependency\Client\StorageToStoreClientInterface
     */
    protected $storeClient;

    /**
     * @var \Spryker\Client\Storage\Dependency\Client\StorageToLocaleClientInterface
     */
    protected $localeClient;

    /**
     * @var \Spryker\Client\Storage\StorageConfig
     */
    protected $config;

    /**
     * @var string|null
     */
    protected static $storeName;

    /**
     * @var string|null
     */
    protected static $locale;

    /**
     * @param \Spryker\Client\Storage\Dependency\Client\StorageToStoreClientInterface $storeClient
     * @param \Spryker\Client\Storage\Dependency\Client\StorageToLocaleClientInterface $localeClient
     * @param \Spryker\Client\Storage\StorageConfig $config
     */
    public function __construct(
        StorageToStoreClientInterface $storeClient,
        StorageToLocaleClientInterface $localeClient,
        StorageConfig $config
    ) {
        $this->storeClient = $storeClient;
        $this->localeClient = $localeClient;
        $this->config = $config;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return string
     */
    public function generateCacheKey(?Request $request = null): string
    {
        if (!$this->config->isStorageCachingEnabled()) {
            return '';
        }

        return $this->generateCacheKeyFromRequest($request);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return string
     */
    protected function generateCacheKeyFromRequest(?Request $request = null): string
    {
        $request = $this->prepareRequest($request);
        $requestUri = $request->getRequestUri();
        $serverName = $request->server->get('SERVER_NAME');
        $queryStringParameters = $request->query->all();

        if (!$requestUri || !$serverName) {
            return '';
        }

        $urlSegments = strtok($requestUri, '?');

        $queryStringParametersFragment = $this->buildQueryStringParametersFragment($queryStringParameters);
        $cacheKey = $this->buildCacheKey($urlSegments, $queryStringParametersFragment);

        return $cacheKey;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function prepareRequest(?Request $request = null): Request
    {
        return $request ?? Request::createFromGlobals();
    }

    /**
     * @param array<string> $queryStringParameters
     *
     * @return string
     */
    protected function buildQueryStringParametersFragment(array $queryStringParameters): string
    {
        $allowedQueryStringParametersList = $this->config->getAllowedGetParametersList();

        if (count($allowedQueryStringParametersList) === 0) {
            return '';
        }

        $allowedQueryStringParameters = array_intersect_key($queryStringParameters, array_flip($allowedQueryStringParametersList));

        if (count($allowedQueryStringParameters) === 0) {
            return '';
        }

        ksort($allowedQueryStringParameters);

        return sprintf('?%s', http_build_query($allowedQueryStringParameters));
    }

    /**
     * @param string $urlSegments
     * @param string $queryStringParametersKey
     *
     * @return string
     */
    protected function buildCacheKey(string $urlSegments, string $queryStringParametersKey): string
    {
        $storeName = '';
        if ($this->storeClient->isCurrentStoreDefined()) {
            $storeName = $this->getStoreName();
        }

        $locale = $this->localeClient->getCurrentLocale();

        return implode(static::KEY_NAME_SEPARATOR, [
            $storeName,
            $locale,
            static::KEY_NAME_PREFIX,
            sprintf('%s%s', $urlSegments, $queryStringParametersKey),
        ]);
    }

    /**
     * @return string
     */
    protected function getStoreName(): string
    {
        if (static::$storeName === null) {
            static::$storeName = $this->storeClient->getCurrentStore()->getName();
        }

        return static::$storeName;
    }

    /**
     * @return string
     */
    protected function getLocale(): string
    {
        if (static::$locale === null) {
            static::$locale = $this->localeClient->getCurrentLocale();
        }

        return static::$locale;
    }
}
