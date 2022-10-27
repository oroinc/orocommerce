<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Handler\ProductPriceDeleteHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use PHPUnit\Framework\TestCase;

class ProductPriceDeleteHandlerTest extends TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceManager|\PHPUnit\Framework\MockObject\MockObject */
    private $priceManager;

    /** @var ProductPriceDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->priceManager = $this->createMock(PriceManager::class);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new EntityDeleteHandlerExtension();
        $extension->setDoctrine($this->doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects($this->any())
            ->method('getHandlerExtension')
            ->with(ProductPrice::class)
            ->willReturn($extension);

        $this->handler = new ProductPriceDeleteHandler($this->priceManager);
        $this->handler->setDoctrine($this->doctrine);
        $this->handler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $this->handler->setExtensionRegistry($extensionRegistry);
    }

    public function testDelete()
    {
        $productPrice = new ProductPrice();

        $this->priceManager->expects(self::once())
            ->method('remove')
            ->with($productPrice);
        $this->priceManager->expects(self::once())
            ->method('flush');

        $this->handler->delete($productPrice);
    }
}
