<?php

namespace Oro\Bundle\SaleBundle\Provider;

/**
 * Defines the contract for providing available options.
 *
 * Implementations return a list of available options for use in forms, configurations,
 * or other contexts where option selection is required.
 */
interface OptionsProviderInterface
{
    /**
     * @return array
     */
    public function getOptions();
}
