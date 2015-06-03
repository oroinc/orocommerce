<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\PricingBundle\Form\Extension\AbstractPriceListExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class AbstractPriceListExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildForm()
    {
        /** @var AbstractPriceListExtension $extension */
        $extension = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Form\Extension\AbstractPriceListExtension')
            ->disableOriginalConstructor()
            ->setMethods(['getExtendedType', 'onPostSetData', 'onPostSubmit'])
            ->getMock();

        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'priceList',
                PriceListSelectType::NAME,
                [
                    'label' => 'orob2b.pricing.pricelist.entity_label',
                    'required' => false,
                    'mapped' => false,
                ]
            );
        $builder->expects($this->exactly(2))
            ->method('addEventListener');
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$extension, 'onPostSetData']);
        $builder->expects($this->at(2))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$extension, 'onPostSubmit']);

        $extension->buildForm($builder, []);
    }
}
