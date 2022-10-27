<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use Oro\Bundle\TaxBundle\Form\Extension\OrderLineItemsCollectionTypeExtension;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OrderLineItemsCollectionTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxationSettingsProvider;

    /** @var TaxValueManager|\PHPUnit\Framework\MockObject\MockObject */
    private $taxValueManager;

    /** @var OrderLineItemsCollectionTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->taxValueManager = $this->createMock(TaxValueManager::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->extension = new OrderLineItemsCollectionTypeExtension(
            $this->taxationSettingsProvider,
            $this->taxValueManager
        );
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals(
            [OrderLineItemsCollectionType::class],
            OrderLineItemsCollectionTypeExtension::getExtendedTypes()
        );
    }

    public function testBuildViewWhenTaxationIsDisabled()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->taxValueManager->expects($this->never())
            ->method('preloadTaxValues');

        $form = $this->createMock(FormInterface::class);
        $this->extension->buildView(new FormView(), $form, []);
    }

    public function testBuildViewWhenTaxationIsEnabled()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $lineItems = [
            $this->getEntity(OrderLineItem::class, ['id' => 7]),
            $this->getEntity(OrderLineItem::class, ['id' => 5]),
            $this->getEntity(OrderLineItem::class),
            $this->getEntity(OrderLineItem::class, ['id' => 10])
        ];
        $expectedIds = [7, 5, null, 10];

        $this->taxValueManager->expects($this->once())
            ->method('preloadTaxValues')
            ->with(OrderLineItem::class, $expectedIds);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn(new ArrayCollection($lineItems));

        $this->extension->buildView(new FormView(), $form, []);
    }
}
