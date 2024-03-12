<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderLineItemChecksumListener;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderLineItemProductListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use PHPUnit\Framework\TestCase;

trait OrderLineItemTypeTrait
{
    protected function createOrderLineItemType(
        TestCase $testCase,
        array $availableProductUnitsWithPrecision
    ): OrderLineItemType {
        $productUnitsProvider = $testCase->createMock(ProductUnitsProvider::class);
        $productUnitsProvider
            ->method('getAvailableProductUnitsWithPrecision')
            ->willReturn($availableProductUnitsWithPrecision);

        $lineItemChecksumGenerator = $testCase->createMock(LineItemChecksumGeneratorInterface::class);
        $lineItemChecksumGenerator
            ->method('getChecksum')
            ->willReturnCallback(
                function (ProductLineItemInterface $lineItem) {
                    return $lineItem->getProduct()?->isKit()
                        ? ($lineItem->getProduct()?->getId()
                            . '|' . $lineItem->getProductUnit()?->getCode()
                            . '|' . $lineItem->getQuantity())
                        : '';
                }
            );

        $entityStateChecker = $testCase->createMock(EntityStateChecker::class);
        $entityStateChecker
            ->method('getOriginalEntityFieldData')
            ->willReturn(null);

        $formType = new OrderLineItemType(
            $productUnitsProvider,
            new OrderLineItemProductListener($entityStateChecker),
            new OrderLineItemChecksumListener($lineItemChecksumGenerator)
        );
        $formType->setDataClass(OrderLineItem::class);
        $formType->setSectionProvider($testCase->createMock(SectionProvider::class));

        return $formType;
    }
}
