<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\PromotionBundle\Api\Processor\AddPromotionDiscounts;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use PHPUnit\Framework\TestCase;

class AddPromotionDiscountsTest extends TestCase
{
    /** @var AppliedDiscountsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $appliedDiscountsProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CustomizeLoadedDataContext */
    private $context;

    /** @var AddPromotionDiscounts */
    private $processor;

    protected function setUp()
    {
        $this->appliedDiscountsProvider = $this->createMock(AppliedDiscountsProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->context = new CustomizeLoadedDataContext();

        $this->processor = new AddPromotionDiscounts(
            $this->appliedDiscountsProvider,
            $this->doctrineHelper
        );
    }

    public function testProcessResultNotArray()
    {
        $this->context->setResult(null);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResult());
    }

    public function testProcessNoConfig()
    {
        $data = [];

        $this->context->setResult($data);
        $this->context->setConfig(null);
        $this->processor->process($this->context);
        self::assertEquals($data, $this->context->getResult());
    }

    public function testProcessResultHasNoId()
    {
        $data = ['id' => 12];
        $config = new EntityDefinitionConfig();
        $config->addField('discount');
        $config->addField('shippingDiscount');

        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals($data, $this->context->getResult());
    }

    public function testProcessNoOrderForProvidedId()
    {
        $id = 1;
        $data = ['id' => $id];
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('discount');
        $config->addField('shippingDiscount');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn(null);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->willReturn($orderRepository);

        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals($data, $this->context->getResult());
    }

    public function testProcess()
    {
        $id = 1;
        $order = new Order();
        $discount = 1.5;
        $shippingDiscount = 3.4;
        $data = ['id' => $id];
        $config = new EntityDefinitionConfig();
        $config->addField('id');
        $config->addField('discount');
        $config->addField('shippingDiscount');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($order);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->willReturn($orderRepository);

        $this->appliedDiscountsProvider->expects(self::once())
            ->method('getDiscountsAmountByOrder')
            ->with($order)
            ->willReturn($discount);
        $this->appliedDiscountsProvider->expects(self::once())
            ->method('getShippingDiscountsAmountByOrder')
            ->with($order)
            ->willReturn($shippingDiscount);

        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'id'               => $id,
                'discount'         => $discount,
                'shippingDiscount' => $shippingDiscount
            ],
            $this->context->getResult()
        );
    }
}
