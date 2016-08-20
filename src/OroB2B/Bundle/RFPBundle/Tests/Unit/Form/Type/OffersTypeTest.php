<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\RFPBundle\Form\Type\OffersType;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OffersTypeTest extends FormIntegrationTestCase
{
    public function testEmptyOptions()
    {
        $form = $this->factory->create(new OffersType());
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertTrue($form->getConfig()->getOption('choices_as_values'));
        $this->assertInternalType('array', $form->getConfig()->getOption('offers'));
        $this->assertInternalType('array', $form->getConfig()->getOption('choices'));
    }

    public function testOffersOption()
    {
        $offers = [['offer1'], ['offer2']];
        $form = $this->factory->create(new OffersType(), null, ['offers' => $offers]);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertTrue($form->getConfig()->getOption('choices_as_values'));
        $this->assertInternalType('array', $form->getConfig()->getOption('offers'));
        $this->assertEquals($offers, $form->getConfig()->getOption('offers'));
        $this->assertInternalType('array', $form->getConfig()->getOption('choices'));
        $this->assertEquals([0, 1], $form->getConfig()->getOption('choices'));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage "offers" with value 1 is expected to be of type "array", but is of type "integer".
     */
    public function testOffersOptionInvalid()
    {
        $form = $this->factory->create(new OffersType(), null, ['offers' => 1]);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertTrue($form->getConfig()->getOption('choices_as_values'));
        $this->assertInternalType('array', $form->getConfig()->getOption('offers'));
        $this->assertInternalType('array', $form->getConfig()->getOption('choices'));
    }

    public function testName()
    {
        $form = $this->factory->create(new OffersType());
        $this->assertEquals(OffersType::NAME, $form->getName());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $formType = new OffersType();

        /* @var $form FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formType->finishView($view, $form, ['offers' => []]);

        $this->assertEquals(['offers' => [], 'value' => null, 'attr' => []], $view->vars);
    }
}
