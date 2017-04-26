<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory\Settings;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;

interface ApruveSettingsRestClientFactoryInterface
{
    /**
     * @param ApruveSettings $apruveSettings
     *
     * @return ApruveRestClientInterface
     */
    public function create(ApruveSettings $apruveSettings);
}
