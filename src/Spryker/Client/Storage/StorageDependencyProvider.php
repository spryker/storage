<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Storage;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Storage\Dependency\Client\StorageToLocaleClientBridge;
use Spryker\Client\Storage\Dependency\Client\StorageToStoreClientBridge;
use Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface;

/**
 * @method \Spryker\Client\Storage\StorageConfig getConfig()
 */
class StorageDependencyProvider extends AbstractDependencyProvider
{
    /**
     * @var string
     */
    public const CLIENT_STORE = 'CLIENT_STORE';

    /**
     * @var string
     */
    public const CLIENT_LOCALE = 'CLIENT_LOCALE';

    /**
     * @var string
     */
    public const STORAGE_CLIENT = 'storage client';

    /**
     * @var string
     */
    public const PLUGIN_STORAGE = 'PLUGIN_STORAGE';

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);

        $container->set(static::STORAGE_CLIENT, function (Container $container) {
            return $container->getLocator()->storage()->client();
        });

        $container->set(static::CLIENT_STORE, function (Container $container) {
            return new StorageToStoreClientBridge($container->getLocator()->store()->client());
        });

        $container->set(static::CLIENT_LOCALE, function (Container $container) {
            return new StorageToLocaleClientBridge($container->getLocator()->locale()->client());
        });

        $container = $this->addStoragePlugin($container);

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addStoragePlugin(Container $container): Container
    {
        $container->set(static::PLUGIN_STORAGE, function (Container $container) {
            return $this->getStoragePlugin();
        });

        return $container;
    }

    /**
     * @return \Spryker\Client\StorageExtension\Dependency\Plugin\StoragePluginInterface|null
     */
    protected function getStoragePlugin(): ?StoragePluginInterface
    {
        return null;
    }
}
