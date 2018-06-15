<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RFPBundle\Form\Type\OffersType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OffersTypeTest extends FormIntegrationTestCase
{
    public function testEmptyOptions()
    {
        $form = $this->factory->create(OffersType::class);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertInternalType('array', $form->getConfig()->getOption('offers'));
        $this->assertInternalType('array', $form->getConfig()->getOption('choices'));
    }

    public function testOffersOption()
    {
        $offers = [['offer1'], ['offer2']];
        $form = $this->factory->create(OffersType::class, null, ['offers' => $offers]);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
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
        $form = $this->factory->create(OffersType::class, null, ['offers' => 1]);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertInternalType('array', $form->getConfig()->getOption('offers'));
        $this->assertInternalType('array', $form->getConfig()->getOption('choices'));
    }

    public function testFinishView()
    {
        $view = new FormView();
        $formType = new OffersType();

        /* @var $form FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $formType->finishView($view, $form, ['offers' => []]);

        $this->assertEquals(['offers' => [], 'value' => null, 'attr' => []], $view->vars);
    }
}
