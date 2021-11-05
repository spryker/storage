<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Storage\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\Storage\Business\StorageFacadeInterface getFacade()
 * @method \Spryker\Zed\Storage\Communication\StorageCommunicationFactory getFactory()
 */
class StorageDeleteAllConsole extends Console
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'storage:delete';

    /**
     * @var string
     */
    public const DESCRIPTION = 'This command will delete all keys from storage.';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::DESCRIPTION);

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->getFacade()->getTotalCount() === 0) {
            $this->info('Storage is empty');

            return static::CODE_SUCCESS;
        }

        $this->info('Delete all keys from storage');
        $deletedKeyCount = $this->getFacade()->deleteAll();
        $this->info(sprintf('Deleted "<fg=green>%s</>" keys from storage', $deletedKeyCount));

        return static::CODE_SUCCESS;
    }
}
