<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Storage\Cache;

use Spryker\Client\Storage\Cache\StorageCacheReplaceStrategy;
use SprykerTest\Client\Storage\Helper\CacheDataProvider;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Storage
 * @group Cache
 * @group StorageCacheReplaceStrategyTest
 * Add your own group annotations below this line
 */
class StorageCacheReplaceStrategyTest extends AbstractStorageCacheStrategyTest
{
    protected function testStrategy(string $testType): void
    {
        $this->setCachedKeysByType($testType);

        $replaceStrategy = new StorageCacheReplaceStrategy(
            $this->storageCacheStrategyHelper,
        );
        $replaceStrategy->updateCache(static::TEST_CACHE_KEY);

        $expectedOutput = $this->cacheDataProvider->getExpectedOutputForReplaceStrategy($testType);
        $this->assertSame(
            $this->storageClientMock->getCachedKeys(),
            $expectedOutput,
        );
    }

    public function testReplaceStrategyWithNewKeys(): void
    {
        $this->testStrategy(CacheDataProvider::TEST_TYPE_NEW_KEYS);
    }

    public function testReplaceStrategyWithUsedKeys(): void
    {
        $this->testStrategy(CacheDataProvider::TEST_TYPE_USED_KEYS);
    }

    public function testReplaceStrategyWithUnusedKeys(): void
    {
        $this->testStrategy(CacheDataProvider::TEST_TYPE_UNUSED_KEYS);
    }

    public function testReplaceStrategyWithNewAndUsedKeys(): void
    {
        $this->testStrategy(CacheDataProvider::TEST_TYPE_NEW_AND_USED_KEYS);
    }

    public function testReplaceStrategyWithNewAndUsedAndUnusedKeys(): void
    {
        $this->testStrategy(CacheDataProvider::TEST_TYPE_NEW_AND_USED_AND_UNUSED_KEYS);
    }

    public function testReplaceStrategyWithNoKeys(): void
    {
        $this->testStrategy(CacheDataProvider::TEST_TYPE_NO_KEYS);
    }
}
