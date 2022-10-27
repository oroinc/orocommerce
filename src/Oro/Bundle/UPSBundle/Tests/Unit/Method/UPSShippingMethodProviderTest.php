<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider;

class UPSShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    const CHANNEL_TYPE = 'channel_type';

    /** @var \PHPUnit\Framework\MockObject\MockObject|IntegrationShippingMethodFactoryInterface */
    private $methodBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->methodBuilder = $this->createMock(IntegrationShippingMethodFactoryInterface::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
    }

    public function testConstructor()
    {
        new UPSShippingMethodProvider(static::CHANNEL_TYPE, $this->doctrineHelper, $this->methodBuilder);
    }
}
