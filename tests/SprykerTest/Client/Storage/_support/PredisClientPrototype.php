<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Storage;

use Predis\Client;

class PredisClientPrototype extends Client
{
    public function keys($pattern)
    {
        // intentionally empty for mockability
    }

    public function scan($cursor, $options = null)
    {
        // intentionally empty for mockability
    }

    public function dbSize()
    {
        // intentionally empty for mockability
    }
}
