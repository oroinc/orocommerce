<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodProvider;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use PHPUnit\Framework\TestCase;

class FixedProductMethodProviderTest extends TestCase
{
    public const CHANNEL_TYPE = 'channel_type';

    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected IntegrationShippingMethodFactoryInterface $methodBuilder;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected DoctrineHelper $doctrineHelper;

    protected function setUp(): void
    {
        $this->methodBuilder = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
    }

    public function testConstructor(): void
    {
        new FixedProductMethodProvider(static::CHANNEL_TYPE, $this->doctrineHelper, $this->methodBuilder);
    }
}
