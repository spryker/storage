<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Storage\Cache\CacheKey;

use Codeception\Test\Unit;
use Spryker\Client\Storage\StorageConfig;
use Spryker\Client\Storage\StorageFactory;
use Spryker\Shared\Kernel\Store;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Storage
 * @group Cache
 * @group CacheKey
 * @group CacheKeyGeneratorTest
 * Add your own group annotations below this line
 *
 * @property \SprykerTest\Client\Storage\StorageClientTester $tester
 */
class CacheKeyGeneratorTest extends Unit
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
     * @var \Spryker\Client\Storage\Cache\CacheKey\CacheKeyGeneratorInterface
     */
    protected $cacheKeyGenerator;

    /**
     * @var \SprykerTest\Client\Storage\StorageClientTester
     */
    protected $tester;

    /**
     * @var array<string>
     */
    protected $allowedQueryStringParameters = [
        'allowedParameter1',
        'allowedParameter2',
    ];

    /**
     * @var \Spryker\Client\Storage\StorageConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storageConfigMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpStorageConfigMock();
        $this->setUpCacheKeyGenerator();
    }

    /**
     * @dataProvider generatesEmptyKeyWhenServerNameOrUriAreEmptyProvider
     *
     * @param string $serverName
     * @param string $requestUri
     * @param bool $expectedResult
     *
     * @return void
     */
    public function testGeneratesEmptyKeyWhenServerNameOrUriAreEmpty(string $serverName, string $requestUri, bool $expectedResult): void
    {
        // Arrange
        $request = new Request();
        $request->server->set('SERVER_NAME', $serverName);
        $request->server->set('REQUEST_URI', $requestUri);

        $this->storageConfigMock->method('isStorageCachingEnabled')->willReturn(true);

        // Act
        $cacheKey = $this->cacheKeyGenerator->generateCacheKey($request);

        // Assert
        $this->assertSame($expectedResult, !$cacheKey);
    }

    /**
     * @return array
     */
    public function generatesEmptyKeyWhenServerNameOrUriAreEmptyProvider(): array
    {
        return [
            'server name empty' => ['', 'uri', true],
            'request uri empty' => ['server name', '', true],
            'server name and request uri empty' => ['', '', true],
            'server name and request uri not empty' => ['server name', 'uri', false],
        ];
    }

    /**
     * @dataProvider generatesCacheKeyProvider
     *
     * @param string $expectedCacheKeyTemplate
     * @param string $requestUri
     * @param array<string> $queryStringParameters
     * @param bool $isCacheEnabled
     *
     * @return void
     */
    public function testGeneratesCacheKey(
        string $expectedCacheKeyTemplate,
        string $requestUri,
        array $queryStringParameters = [],
        bool $isCacheEnabled = true
    ): void {
        // Arrange
        $request = new Request();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('REQUEST_URI', $requestUri);
        $request->query = new ParameterBag($queryStringParameters);

        $this->storageConfigMock->method('isStorageCachingEnabled')->willReturn($isCacheEnabled);

        // Act
        $actualCacheKey = $this->cacheKeyGenerator->generateCacheKey($request);

        $expectedCacheKey = sprintf(
            $expectedCacheKeyTemplate,
            $this->tester->getLocator()->store()->client()->getCurrentStore()->getNameOrFail(),
            $this->tester->getLocator()->locale()->client()->getCurrentLocale(),
        );

        // Assert
        $this->assertSame($expectedCacheKey, $actualCacheKey);
    }

    /**
     * @return array
     */
    public function generatesCacheKeyProvider(): array
    {
        return [
            'no query string parameters' => [
                $this->buildExpectedCacheKeyTemplate('/en/request-uri'),
                '/en/request-uri',
            ],
            'one allowed query string parameter' => [
                $this->buildExpectedCacheKeyTemplate('/en/another-request-uri?allowedParameter1=1'),
                '/en/another-request-uri',
                [
                    'allowedParameter1' => '1',
                ],
            ],
            'both allowed query string parameters' => [
                $this->buildExpectedCacheKeyTemplate('/en/yet-another-request-uri?allowedParameter1=1&allowedParameter2=2'),
                '/en/yet-another-request-uri',
                [
                    'allowedParameter1' => '1',
                    'allowedParameter2' => '2',
                ],
            ],
            'one allowed and one disallowed query string parameter' => [
                $this->buildExpectedCacheKeyTemplate('/en/cameras-&-camcorders?allowedParameter2=2'),
                '/en/cameras-&-camcorders',
                [
                    'disallowedParameter1' => '1',
                    'allowedParameter2' => '2',
                ],
            ],
            'only disallowed query string parameters' => [
                $this->buildExpectedCacheKeyTemplate('/en/computers'),
                '/en/computers',
                [
                    'disallowedParameter1' => '1',
                    'disallowedParameter2' => '2',
                ],
            ],
            'cache is disabled' => [
                '',
                '/',
                [],
                false,
            ],
        ];
    }

    /**
     * @param string $expectedRequestUriFragment
     *
     * @return string
     */
    protected function buildExpectedCacheKeyTemplate(string $expectedRequestUriFragment): string
    {
        return implode(static::KEY_NAME_SEPARATOR, [$this->buildCacheKeyPrefixTemplate(), $expectedRequestUriFragment]);
    }

    /**
     * @return string
     */
    protected function buildCacheKeyPrefixTemplate(): string
    {
        return implode(static::KEY_NAME_SEPARATOR, [
            '%s',
            '%s',
            static::KEY_NAME_PREFIX,
        ]);
    }

    /**
     * @return \Spryker\Shared\Kernel\Store
     */
    protected function getStore(): Store
    {
        return Store::getInstance();
    }

    /**
     * @return \Spryker\Client\Storage\StorageFactory
     */
    protected function createStorageFactory(): StorageFactory
    {
        $storageFactory = new StorageFactory();
        $storageFactory->setConfig($this->storageConfigMock);

        return $storageFactory;
    }

    /**
     * @return void
     */
    protected function setUpStorageConfigMock(): void
    {
        $this->storageConfigMock = $this->createMock(StorageConfig::class);
        $this->storageConfigMock->method('getAllowedGetParametersList')->willReturn(
            $this->allowedQueryStringParameters,
        );
    }

    /**
     * @return void
     */
    protected function setUpCacheKeyGenerator(): void
    {
        $this->cacheKeyGenerator = $this->createStorageFactory()->createCacheKeyGenerator();
    }
}
