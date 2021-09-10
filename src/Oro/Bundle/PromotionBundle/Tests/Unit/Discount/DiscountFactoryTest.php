<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedDiscountException;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedTypeException;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DiscountFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ContainerInterface|MockObject */
    protected $container;

    /** @var DiscountFactory */
    protected $discountFactory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->discountFactory = new DiscountFactory($this->container);
    }

    public function testAddType()
    {
        $type1 = 'oro_promotion.test_type1';
        $serviceName1 = 'oro_promotion.test_type1.service';

        $type2 = 'oro_promotion.test_type2';
        $serviceName2 = 'oro_promotion.test_type2.service';

        $this->discountFactory->addType($type1, $serviceName1);
        $this->discountFactory->addType($type2, $serviceName2);

        $this->container->expects(static::exactly(2))
            ->method('get')
            ->withConsecutive(
                [$serviceName1],
                [$serviceName2]
            )
            ->willReturn($this->createMock(DiscountInterface::class));

        $configuration1 = $this->createMock(DiscountConfiguration::class);
        $configuration1->method('getOptions')->willReturn([]);
        $configuration1->method('getType')->willReturn($type1);

        $this->discountFactory->create($configuration1);

        $configuration2 = $this->createMock(DiscountConfiguration::class);
        $configuration2->method('getOptions')->willReturn([]);
        $configuration2->method('getType')->willReturn($type2);
        $this->discountFactory->create($configuration2);
    }

    public function testCreate()
    {
        $type = 'oro_promotion.test_type';
        $serviceName = 'oro_promotion.test_type.service';

        /** @var DiscountInterface|MockObject $discount */
        $discount = $this->createMock(DiscountInterface::class);

        $configurationOptions = ['option' => 'option_value'];

        $configuration = new DiscountConfiguration();
        $configuration->setType($type);
        $configuration->setOptions($configurationOptions);

        $this->discountFactory->addType($type, $serviceName);

        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceName)
            ->willReturn($discount);

        $discount->expects($this->once())
            ->method('configure')
            ->with($configurationOptions);

        $this->discountFactory->create($configuration);
    }

    public function testCreateWithPromotion()
    {
        $type = 'oro_promotion.test_type';
        $serviceName = 'oro_promotion.test_type.service';

        /** @var DiscountInterface|MockObject $discount */
        $discount = $this->createMock(DiscountInterface::class);

        $configurationOptions = ['option' => 'option_value'];

        $configuration = new DiscountConfiguration();
        $configuration->setType($type);
        $configuration->setOptions($configurationOptions);

        $this->discountFactory->addType($type, $serviceName);

        $promotion = new Promotion();

        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceName)
            ->willReturn($discount);

        $discount->expects($this->once())
            ->method('configure')
            ->with($configurationOptions);

        $discount->expects($this->once())
            ->method('setPromotion')
            ->with($promotion);

        $this->discountFactory->create($configuration, $promotion);
    }

    public function testCreateUnsupportedTypeException()
    {
        $configuration = new DiscountConfiguration();
        $configuration->setType('unsupported_type');

        $this->discountFactory->addType('oro_promotion.test_type', 'oro_promotion.test_type.service');

        $this->container->expects($this->never())
            ->method($this->anything());

        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage('Unknown discount type unsupported_type');

        $this->discountFactory->create($configuration);
    }

    public function testCreateUnsupportedDiscountException()
    {
        $type = 'oro_promotion.test_type';
        $serviceName = 'oro_promotion.test_type.service';

        $configuration = new DiscountConfiguration();
        $configuration->setType($type);

        $this->discountFactory->addType($type, 'oro_promotion.test_type.service');

        $this->container->expects($this->once())
            ->method('get')
            ->with($serviceName)
            ->willReturn(new \stdClass());

        $this->expectException(UnsupportedDiscountException::class);
        $this->expectExceptionMessage('Discount "stdClass" should implement DiscountInterface.');

        $this->discountFactory->create($configuration);
    }
}
