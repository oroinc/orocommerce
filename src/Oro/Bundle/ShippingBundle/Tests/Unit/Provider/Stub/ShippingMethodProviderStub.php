<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingMethodProviderStub implements ShippingMethodProviderInterface
{
    const METHOD_IDENTIFIER = 'test';
    const METHOD_TYPE_IDENTIFIER = 'test_type';

    /** @var ShippingMethodStub */
    protected $method;

    public function __construct()
    {
        $type = new ShippingMethodTypeStub();
        $type->setIdentifier(self::METHOD_TYPE_IDENTIFIER);

        $method = new ShippingMethodStub();
        $method->setIdentifier(self::METHOD_IDENTIFIER)
            ->setIsGrouped(false)
            ->setTypes([$type]);

        $this->method = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethods()
    {
        return [$this->method->getIdentifier() => $this->method];
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod($name)
    {
        if ($name === $this->method->getIdentifier()) {
            return $this->method;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasShippingMethod($name)
    {
        return $name === $this->method->getIdentifier();
    }
}
