<?php

namespace Oro\Bundle\SaleBundle\Provider;

/**
 * Defines the contract for option providers that support default values.
 *
 * Combines the functionality of {@see OptionsProviderInterface} and {@see DefaultOptionAwareInterface},
 * allowing implementations to provide both a list of available options and a default option.
 */
interface OptionProviderWithDefaultValueInterface extends OptionsProviderInterface, DefaultOptionAwareInterface
{
}
