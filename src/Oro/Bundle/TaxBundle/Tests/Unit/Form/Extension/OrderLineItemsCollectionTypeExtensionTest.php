<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use Oro\Bundle\TaxBundle\Form\Extension\OrderLineItemsCollectionTypeExtension;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OrderLineItemsCollectionTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $taxationSettingsProvider;

    /**
     * @var TaxValueManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $taxValueManager;

    /**
     * @var OrderLineItemsCollectionTypeExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->taxValueManager = $this->getMockBuilder(TaxValueManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxationSettingsProvider = $this->getMockBuilder(TaxationSettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrderLineItemsCollectionTypeExtension(
            $this->taxationSettingsProvider,
            $this->taxValueManager,
            OrderLineItemsCollectionType::NAME
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OrderLineItemsCollectionType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildViewWhenTaxationIsDisabled()
    {
        $this->taxationSettingsProvider
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->taxValueManager
            ->expects($this->never())
            ->method('preloadTaxValues');

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $this->extension->buildView(new FormView(), $form, []);
    }

    public function testBuildViewWhenTaxationIsEnabled()
    {
        $this->taxationSettingsProvider
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $lineItems = [
            $this->getEntity(OrderLineItem::class, ['id' => 7]),
            $this->getEntity(OrderLineItem::class, ['id' => 5]),
            $this->getEntity(OrderLineItem::class),
            $this->getEntity(OrderLineItem::class, ['id' => 10])
        ];
        $expectedIds = [7, 5, null, 10];

        $this->taxValueManager
            ->expects($this->once())
            ->method('preloadTaxValues')
            ->with(OrderLineItem::class, $expectedIds);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn(new ArrayCollection($lineItems));

        $this->extension->buildView(new FormView(), $form, []);
    }
}
