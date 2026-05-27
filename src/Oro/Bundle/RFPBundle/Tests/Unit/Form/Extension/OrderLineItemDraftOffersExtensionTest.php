<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\RFPBundle\Form\Extension\OrderLineItemDraftOffersExtension;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftOffersExtensionTest extends TestCase
{
    public function testGetExtendedTypes(): void
    {
        $result = iterator_to_array(OrderLineItemDraftOffersExtension::getExtendedTypes());

        self::assertSame([OrderLineItemDraftType::class], $result);
    }
}
