<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;

class PriceListCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPreSubmit()
    {
        $type = new PriceListCollectionType();
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
            ->with(3)
        ;

        $event = new FormEvent($form, $submitted);
        $type->onPreSubmit($event);
        unset($submitted[3]);

        $this->assertEquals($submitted, $event->getData());
    }

    public function testGetName()
    {
        $type = new PriceListCollectionType();
        $this->assertSame(PriceListCollectionType::NAME, $type->getName());
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function getFormMock()
    {
        return $this->getMock('Symfony\Component\Form\FormInterface');
    }
}
