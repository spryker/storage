<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Storage\Redis;

use Codeception\Test\Unit;
use Predis\ClientInterface;
use Spryker\Client\Storage\Redis\Service;
use Spryker\Client\Storage\StorageClient;
use Spryker\Client\Storage\StorageClientInterface;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Storage\StorageConstants;
use SprykerTest\Client\Testify\Helper\ClientHelperTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Storage
 * @group Redis
 * @group ServiceTest
 * Add your own group annotations below this line
 */
class ServiceTest extends Unit
{
    use ClientHelperTrait;

    /**
     * @uses \Spryker\Shared\StorageRedis\StorageRedisConstants::STORAGE_REDIS_PROTOCOL
     *
     * @deprecated Use {@link \SprykerTest\Client\Storage\Redis\ServiceTest::REDIS_SCHEME} instead.
     *
     * @var string
     */
    protected const REDIS_PROTOCOL = 'STORAGE_REDIS:STORAGE_REDIS_PROTOCOL';

    /**
     * @uses \Spryker\Shared\StorageRedis\StorageRedisConstants::STORAGE_REDIS_SCHEME
     *
     * @var string
     */
    protected const REDIS_SCHEME = 'STORAGE_REDIS:STORAGE_REDIS_SCHEME';

    /**
     * @uses \Spryker\Shared\StorageRedis\StorageRedisConstants::STORAGE_REDIS_HOST
     *
     * @var string
     */
    protected const REDIS_HOST = 'STORAGE_REDIS:STORAGE_REDIS_HOST';

    /**
     * @uses \Spryker\Shared\StorageRedis\StorageRedisConstants::STORAGE_REDIS_PORT
     *
     * @var string
     */
    protected const REDIS_PORT = 'STORAGE_REDIS:STORAGE_REDIS_PORT';

    /**
     * @uses \Spryker\Shared\StorageRedis\StorageRedisConstants::STORAGE_REDIS_DATABASE
     *
     * @var string
     */
    protected const REDIS_DATABASE = 'STORAGE_REDIS:STORAGE_REDIS_DATABASE';

    /**
     * @uses \Spryker\Shared\StorageRedis\StorageRedisConstants::STORAGE_REDIS_PASSWORD
     *
     * @var string
     */
    protected const REDIS_PASSWORD = 'STORAGE_REDIS:STORAGE_REDIS_PASSWORD';

    /**
     * @var \SprykerTest\Client\Storage\StorageClientTester
     */
    protected $tester;

    /**
     * @var array
     */
    protected $fixtures = [
        'multi' => [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ],
    ];

    /**
     * @var array
     */
    protected $expected = [
        'multi' => [
            'kv:key1' => 'value1',
            'kv:key2' => 'value2',
            'kv:key3' => 'value3',
        ],
    ];

    /**
     * @var \Spryker\Client\Storage\Redis\Service
     */
    protected $redisService;

    /**
     * @var \Predis\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $clientMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupServerVariable();
        $this->setupConfig();
    }

    /**
     * @return void
     */
    protected function _before(): void
    {
        $this->clientMock = $this->getMockBuilder(ClientInterface::class)
            ->setMethods([
                'keys',
                'scan',
                'dbSize',
                'getProfile',
                'getOptions',
                'connect',
                'disconnect',
                'createCommand',
                'executeCommand',
                'getConnection',
                '__call',
            ])
            ->getMock();

        $this->redisService = new Service(
            $this->clientMock,
        );
    }

    /**
     * @return \Spryker\Client\Storage\StorageClientInterface
     */
    protected function getStorageClient(): StorageClientInterface
    {
        /** @var \Spryker\Client\Storage\StorageClientInterface $storageClient */
        $storageClient = $this->getClientHelper()->getClient();

        return $storageClient;
    }

    /**
     * @return void
     */
    public function testGetAllKeysTriggersRightCommand(): void
    {
        $this->clientMock->expects($this->once())->method('keys')->with($this->equalTo('kv:*'));

        $this->redisService->getAllKeys();
    }

    /**
     * @return void
     */
    public function testGetKeysPassesPatternCorrectly(): void
    {
        $this->clientMock->expects($this->once())->method('keys')->with($this->equalTo('kv:aPattern*'));

        $this->redisService->getKeys('aPattern*');
    }

    /**
     * @return void
     */
    public function testGetMultiWithEmptyKeys(): void
    {
        $requestedKeys = [];

        $this->assertSame($requestedKeys, $this->redisService->getMulti($requestedKeys));
    }

    /**
     * @return void
     */
    public function testGet(): void
    {
        if (!$this->tester->getModuleConfig()->isStorageCachingEnabled()) {
            $this->markTestSkipped('Cache is disabled.');
        }

        $key = 'key';
        $value = 'value';

        $storageClient = new StorageClient();
        $storageClient->set($key, $value);

        $this->assertEquals($value, $storageClient->get($key));
    }

    /**
     * @return void
     */
    public function testGetMultiCached(): void
    {
        if (!$this->tester->getModuleConfig()->isStorageCachingEnabled()) {
            $this->markTestSkipped('Cache is disabled.');
        }

        $storageClient = $this->getStorageClient();
        $storageClient->setMulti($this->fixtures['multi']);

        //Check 0: Returns expected keys and values w/o cache
        $result = $storageClient->getMulti(array_keys($this->fixtures['multi']));
        $this->assertEquals($this->expected['multi'], $result);

        $request = $this->createRequest();
        $storageClient->persistCacheForRequest($request);

        //Reset cache
        $storageClient->setCachedKeys(null);
        $storageClient->resetCache();

        //Warm-up cache
        $storageClient->getMulti(['non-existing-key']);
        $cachedKeys = $storageClient->getCachedKeys();
        $this->assertNotEmpty($cachedKeys);

        //Check 1: Cache is used
        $cachedKeys = array_intersect_key($this->fixtures['multi'], $storageClient->getCachedKeys());
        $this->assertEquals(array_keys($this->fixtures['multi']), array_keys($cachedKeys));

        //Check 2: Returns expected keys and values
        $this->assertEquals($this->expected['multi'], $storageClient->getMulti(array_keys($this->fixtures['multi'])));
    }

    /**
     * @return void
     */
    public function testGetMultiReplaceStrategy(): void
    {
        if (!$this->tester->getModuleConfig()->isStorageCachingEnabled()) {
            $this->markTestSkipped('Cache is disabled.');
        }

        $this->testMultiKeyStrategy(
            StorageConstants::STORAGE_CACHE_STRATEGY_REPLACE,
            $this->fixtures['multi'],
            $this->expected['multi'],
        );
    }

    /**
     * @return void
     */
    public function testGetMultiIncrementalStrategy(): void
    {
        if (!$this->tester->getModuleConfig()->isStorageCachingEnabled()) {
            $this->markTestSkipped('Cache is disabled.');
        }

        $this->testMultiKeyStrategy(
            StorageConstants::STORAGE_CACHE_STRATEGY_INCREMENTAL,
            $this->fixtures['multi'],
            $this->expected['multi'],
        );
    }

    /**
     * @return void
     */
    public function testGetSingleReplaceStrategy(): void
    {
        if (!$this->tester->getModuleConfig()->isStorageCachingEnabled()) {
            $this->markTestSkipped('Cache is disabled.');
        }

        $this->testSingleKeyStrategy(
            StorageConstants::STORAGE_CACHE_STRATEGY_INCREMENTAL,
            'test.replace.key',
            'test.replace.value',
        );
    }

    /**
     * @return void
     */
    public function testGetSingleIncrementalStrategy(): void
    {
        if (!$this->tester->getModuleConfig()->isStorageCachingEnabled()) {
            $this->markTestSkipped('Cache is disabled.');
        }

        $this->testSingleKeyStrategy(
            StorageConstants::STORAGE_CACHE_STRATEGY_INCREMENTAL,
            'test.incremental.key',
            'test.incremental.value',
        );
    }

    /**
     * @param string $strategyName
     * @param array $fixtures
     * @param array $expected
     *
     * @return void
     */
    protected function testMultiKeyStrategy(string $strategyName, array $fixtures, array $expected): void
    {
        $request = $this->createRequest();

        $storageClient = $this->getStorageClient();
        $storageClient->setMulti($fixtures);
        $this->assertEquals($expected, $storageClient->getMulti(array_keys($fixtures)));

        $storageClient->persistCacheForRequest($request, $strategyName);
        $storageClient->setCachedKeys(null);
        $this->assertEquals($expected, $storageClient->getMulti(array_keys($fixtures)));
    }

    /**
     * @param string $strategyName
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    protected function testSingleKeyStrategy(string $strategyName, string $key, string $value): void
    {
        $request = $this->createRequest();

        $storageClient = $this->getStorageClient();
        $storageClient->set($key, $value);
        $this->assertEquals($value, $storageClient->get($key));

        $storageClient->persistCacheForRequest($request, $strategyName);
        $storageClient->setCachedKeys(null);
        $this->assertEquals($value, $storageClient->get($key));
    }

    /**
     * @return void
     */
    protected function setupServerVariable(): void
    {
        $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test/url';
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function createRequest(): Request
    {
        $request = new Request([], [], [], [], [], $_SERVER);

        return $request;
    }

    /**
     * @return void
     */
    public function testGetMultiShouldReturnDataInTheSameOrderAsInput(): void
    {
        // Arrange
        $bufferedData = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
            'd' => 'D',
        ];
        $input = [
            'z',
            'c',
            'b',
            'd',
        ];
        $expectedResult = [
            'kv:z' => '_Z',
            'kv:c' => 'C',
            'kv:b' => 'B',
            'kv:d' => 'D',
        ];

        $storageClient = $this->getStorageClientMock();
        $this->tester->setProtectedProperty($storageClient, 'bufferedValues', $bufferedData);

        // Act
        $actualResult = $storageClient->getMulti($input);

        // Assert
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return \Spryker\Client\Storage\StorageClient|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getStorageClientMock(): StorageClient
    {
        StorageClient::$cachedKeys = [];

        $getMultiFunctionStub = function ($keys) {
            $result = [];
            foreach ($keys as $key) {
                $result['kv:' . $key] = '_' . strtoupper($key);
            }

            return $result;
        };

        $redisService = $this->getMockBuilder(Service::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMulti', '__destruct'])
            ->getMock();
        $redisService
            ->method('getMulti')
            ->willReturnCallback($getMultiFunctionStub);
        $redisService
            ->method('__destruct')
            ->willReturn(true);

        $storageClient = $this->getMockBuilder(StorageClient::class)
            ->setMethods(['loadCacheKeysAndValues', 'getService'])
            ->getMock();
        $storageClient
            ->method('getService')
            ->willReturn($redisService);

        return $storageClient;
    }

    /**
     * @return void
     */
    protected function setupConfig(): void
    {
        $this->tester->setConfig(StorageConstants::STORAGE_REDIS_PROTOCOL, Config::get(static::REDIS_SCHEME, false) ?: Config::get(static::REDIS_PROTOCOL, 'tcp'));
        $this->tester->setConfig(StorageConstants::STORAGE_REDIS_PORT, Config::get(static::REDIS_PORT, 10009));
        $this->tester->setConfig(StorageConstants::STORAGE_REDIS_HOST, Config::get(static::REDIS_HOST, '127.0.0.1'));
        $this->tester->setConfig(StorageConstants::STORAGE_REDIS_DATABASE, Config::get(static::REDIS_DATABASE, 3));
        $this->tester->setConfig(StorageConstants::STORAGE_REDIS_PASSWORD, Config::get(static::REDIS_PASSWORD, false));
    }
}
