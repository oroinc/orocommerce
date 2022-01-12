<?php

namespace Oro\Bundle\VisibilityBundle\Driver;

use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Factory to create targeted partial customer update driver instance(search engine type dependent one).
 */
class CustomerPartialUpdateDriverFactory
{
    /**
     * @param ServiceLocator $locator
     * @param EngineParameters $engineParameters
     * @return CustomerPartialUpdateDriverInterface
     * @throws UnexpectedTypeException
     */
    public static function create(
        ServiceLocator $locator,
        EngineParameters $engineParameters
    ): CustomerPartialUpdateDriverInterface {
        $customerPartialUpdateDriver = $locator->get($engineParameters->getEngineName());
        if (!$customerPartialUpdateDriver instanceof CustomerPartialUpdateDriverInterface) {
            throw new UnexpectedTypeException(
                $customerPartialUpdateDriver,
                CustomerPartialUpdateDriverInterface::class
            );
        }

        return $customerPartialUpdateDriver;
    }
}
