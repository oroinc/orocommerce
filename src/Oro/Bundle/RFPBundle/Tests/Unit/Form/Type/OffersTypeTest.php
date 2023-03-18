<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RFPBundle\Form\Type\OffersType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class OffersTypeTest extends FormIntegrationTestCase
{
    public function testEmptyOptions()
    {
        $form = $this->factory->create(OffersType::class);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertIsArray($form->getConfig()->getOption('offers'));
        $this->assertIsArray($form->getConfig()->getOption('choices'));
    }

    public function testOffersOption()
    {
        $offers = [['offer1'], ['offer2']];
        $form = $this->factory->create(OffersType::class, null, ['offers' => $offers]);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertIsArray($form->getConfig()->getOption('offers'));
        $this->assertEquals($offers, $form->getConfig()->getOption('offers'));
        $this->assertIsArray($form->getConfig()->getOption('choices'));
        $this->assertEquals([0, 1], $form->getConfig()->getOption('choices'));
    }

    public function testOffersOptionInvalid()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage(
            '"offers" with value 1 is expected to be of type "array", but is of type "int".'
        );

        $form = $this->factory->create(OffersType::class, null, ['offers' => 1]);
        $this->assertFalse($form->getConfig()->getOption('mapped'));
        $this->assertTrue($form->getConfig()->getOption('expanded'));
        $this->assertIsArray($form->getConfig()->getOption('offers'));
        $this->assertIsArray($form->getConfig()->getOption('choices'));
    }

    public function testFinishView()
    {
        $view = new FormView();
        $formType = new OffersType();

        $form = $this->createMock(FormInterface::class);

        $formType->finishView($view, $form, ['offers' => []]);

        $this->assertEquals(['offers' => [], 'value' => null, 'attr' => []], $view->vars);
    }
}
