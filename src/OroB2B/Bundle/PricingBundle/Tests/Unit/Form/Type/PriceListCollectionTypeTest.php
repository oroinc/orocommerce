<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;

class PriceListCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListCollectionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new PriceListCollectionType();
    }

    public function testOnPreSubmit()
    {
        $priceList = new PriceList();
        $submitted = [
            ['priceList' => $priceList, 'priority' => 100],
            ['priceList' => $priceList, 'priority' => ''],
            ['priceList' => '', 'priority' => 100],
            ['priceList' => '', 'priority' => ''],
        ];

        $form = $this->getFormMock();

        $form->expects($this->once())
            ->method('remove')
            ->with(3);

        $event = new FormEvent($form, $submitted);
        $this->type->onPreSubmit($event);
        unset($submitted[3]);

        $this->assertEquals($submitted, $event->getData());
    }

    public function testGetName()
    {
        $this->assertSame(PriceListCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertSame(CollectionType::NAME, $this->type->getParent());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $this->type->finishView($view, $this->getFormMock(), ['render_as_widget' => true]);

        $this->assertArrayHasKey('render_as_widget', $view->vars);
        $this->assertTrue($view->vars['render_as_widget']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function getFormMock()
    {
        return $this->getMock('Symfony\Component\Form\FormInterface');
    }
}
