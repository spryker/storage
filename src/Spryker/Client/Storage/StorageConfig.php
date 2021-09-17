<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Storage;

use Spryker\Client\Kernel\AbstractBundleConfig;

class StorageConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return int
     */
    public function getStorageCacheIncrementalStrategyKeySizeLimit()
    {
        return 1000;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getStorageCacheTtl()
    {
        return 86400;
    }

    /**
     * Specification:
     * - Defines parameter names which will be used for kv multi get optimisation.
     * - Please make sure you use an expected case for parameter names.
     *
     * @api
     *
     * @return array<string>
     */
    public function getAllowedGetParametersList(): array
    {
        return [];
    }

    /**
     * @api
     *
     * @return bool
     */
    public function isStorageCachingEnabled(): bool
    {
        return true;
    }
}
