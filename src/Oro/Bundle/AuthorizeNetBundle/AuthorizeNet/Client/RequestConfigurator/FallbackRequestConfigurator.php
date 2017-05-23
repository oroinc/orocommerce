<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator;

use net\authorize\api\contract\v1 as AnetAPI;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FallbackRequestConfigurator implements RequestConfiguratorInterface
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -10;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $options)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(AnetAPI\CreateTransactionRequest $request, array &$options)
    {
        foreach ($options as $key => $value) {
            $this->propertyAccessor->setValue($request, $key, $value);
        }
    }
}
