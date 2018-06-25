<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\PromotionBundle\Api\Processor\AddPromotionDiscounts;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class AddPromotionDiscountsTest extends TestCase
{
    /**
     * @var AppliedDiscountsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $appliedDiscountsProvider;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var CustomizeLoadedDataContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var AddPromotionDiscounts
     */
    private $processor;

    protected function setUp()
    {
        $this->appliedDiscountsProvider = $this->createMock(AppliedDiscountsProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->context = $this->createMock(CustomizeLoadedDataContext::class);

        $this->processor = new AddPromotionDiscounts(
            $this->appliedDiscountsProvider,
            $this->doctrineHelper
        );
    }

    public function testProcessWrongContext()
    {
        $context = $this->createMock(ContextInterface::class);

        $context->expects(static::never())
            ->method('setResult');

        $this->processor->process($context);
    }

    public function testProcessResultNotArray()
    {
        $this->context->expects(static::once())
            ->method('getResult')
            ->willReturn(null);

        $this->context->expects(static::never())
            ->method('setResult');

        $this->processor->process($this->context);
    }

    public function testProcessNoConfig()
    {
        $this->context->expects(static::once())
            ->method('getResult')
            ->willReturn([]);

        $this->context->expects(static::once())
            ->method('getConfig')
            ->willReturn(null);

        $this->context->expects(static::never())
            ->method('setResult');

        $this->processor->process($this->context);
    }

    public function testProcessResultHasNoId()
    {
        $config = $this->createMock(EntityDefinitionConfig::class);
        $config->expects(static::once())
            ->method('findFieldNameByPropertyPath')
            ->with('id')
            ->willReturn('renamed_id');

        $this->context->expects(static::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->context->expects(static::once())
            ->method('getResult')
            ->willReturn([
                'id' => 12
            ]);

        $this->context->expects(static::never())
            ->method('setResult');

        $this->processor->process($this->context);
    }

    public function testProcessNoOrderForProvidedId()
    {
        $id = 1;

        $config = $this->createMock(EntityDefinitionConfig::class);
        $config->expects(static::once())
            ->method('findFieldNameByPropertyPath')
            ->with('id')
            ->willReturn('id');

        $this->context->expects(static::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->context->expects(static::once())
            ->method('getResult')
            ->willReturn([
                'id' => $id
            ]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(static::once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepository')
            ->willReturn($orderRepository);

        $this->context->expects(static::never())
            ->method('setResult');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $id = 1;
        $order = new Order();
        $discount = 1.5;
        $shippingDiscount = 3.4;

        $config = $this->createMock(EntityDefinitionConfig::class);
        $config->expects(static::exactly(3))
            ->method('findFieldNameByPropertyPath')
            ->withConsecutive(['id'], ['discount'], ['shippingDiscount'])
            ->willReturnOnConsecutiveCalls('id', 'discount', 'shippingDiscount');

        $config->expects(static::exactly(2))
            ->method('getField')
            ->withConsecutive(['discount'], ['shippingDiscount'])
            ->willReturn($this->createMock(EntityDefinitionFieldConfig::class));

        $this->context->expects(static::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->context->expects(static::once())
            ->method('getResult')
            ->willReturn([
                'id' => $id
            ]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(static::once())
            ->method('find')
            ->with($id)
            ->willReturn($order);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepository')
            ->willReturn($orderRepository);

        $this->appliedDiscountsProvider->expects(static::once())
            ->method('getDiscountsAmountByOrder')
            ->with($order)
            ->willReturn($discount);

        $this->appliedDiscountsProvider->expects(static::once())
            ->method('getShippingDiscountsAmountByOrder')
            ->with($order)
            ->willReturn($shippingDiscount);

        $this->context->expects(static::once())
            ->method('setResult')
            ->with([
                'id' => $id,
                'discount' => $discount,
                'shippingDiscount' => $shippingDiscount,
            ]);

        $this->processor->process($this->context);
    }
}
