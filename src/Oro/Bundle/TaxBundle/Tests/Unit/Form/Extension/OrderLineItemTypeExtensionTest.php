<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\TaxBundle\Form\Extension\OrderLineItemTypeExtension;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderLineItemTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxationSettingsProvider;

    /**
     * @var TaxProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxProvider;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalProvider;

    /**
     * @var OrderLineItemTypeExtension
     */
    protected $extension;

    /** @var SectionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $sectionProvider;

    protected function setUp()
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->taxationSettingsProvider = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->sectionProvider = $this->createMock('Oro\Bundle\OrderBundle\Form\Section\SectionProvider');

        $this->extension = new OrderLineItemTypeExtension(
            $this->taxationSettingsProvider,
            $taxProviderRegistry,
            $this->totalProvider,
            $this->sectionProvider,
            OrderLineItemType::NAME
        );
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OrderLineItemType::NAME, $this->extension->getExtendedType());
    }

    public function testFinishViewDisabledProvider()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->taxProvider->expects($this->never())->method('getTax');

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $view = new FormView();
        $this->extension->finishView($view, $form, []);
    }

    public function testFinishViewEmptyForm()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxProvider->expects($this->never())->method('getTax');


        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getData')->willReturn(null);
        $view = new FormView();
        $this->extension->finishView($view, $form, []);
    }

    public function testFinishView()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $result = new \ArrayObject();
        $result->offsetSet('Key', 'Result');

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($result);

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getData')->willReturn(new \stdClass());
        $view = new FormView();
        $this->extension->finishView($view, $form, []);

        $this->assertArrayHasKey('result', $view->vars);
        $this->assertEquals($result, $view->vars['result']);
    }

    public function testBuildView()
    {
        $this->sectionProvider->expects($this->once())->method('addSections')
            ->with(
                $this->logicalAnd(
                    $this->isType('string'),
                    $this->equalTo($this->extension->getExtendedType())
                ),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('taxes')
                )
            );

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->extension->buildView($view, $form, []);
    }

    public function testBuildViewTaxDisabled()
    {
        $this->sectionProvider->expects($this->never())->method($this->anything());

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(false);

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->extension->buildView($view, $form, []);
    }
}
