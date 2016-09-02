<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Component\Testing\Unit\AddressFormExtensionTestCase;

abstract class AbstractAddressTypeTest extends AddressFormExtensionTestCase
{
    protected $formType;

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     * @param array $formErrors
     * @param array $formOptions
     */
    protected function checkForm($isValid, $submittedData, $expectedData, $defaultData, $formErrors, $formOptions)
    {
        $form = $this->factory->create(
            $this->formType,
            $defaultData,
            $formOptions
        );
        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());

        if ($form->getErrors(true)->count()) {
            $this->assertNotEmpty($formErrors);
        }

        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->assertArrayHasKey($error->getOrigin()->getName(), $formErrors);

            /** @var ConstraintViolation $violation */
            $violation = $error->getCause();
            $this->assertEquals(
                $formErrors[$error->getOrigin()->getName()],
                $error->getMessage(),
                sprintf('Failed path: %s', $violation->getPropertyPath())
            );
        }
        $this->assertEquals($expectedData, $form->getData());

        $this->assertTrue($form->has('accountAddress'));
        $this->assertTrue($form->get('accountAddress')->getConfig()->hasOption('attr'));
        $this->assertArrayHasKey('data-addresses', $form->get('accountAddress')->getConfig()->getOption('attr'));
        $this->assertInternalType(
            'string',
            $form->get('accountAddress')->getConfig()->getOption('attr')['data-addresses']
        );
        $this->assertInternalType(
            'array',
            json_decode($form->get('accountAddress')->getConfig()->getOption('attr')['data-addresses'], true)
        );
        $this->assertArrayHasKey('data-default', $form->get('accountAddress')->getConfig()->getOption('attr'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge([$this->getValidatorExtension(true)], parent::getExtensions());
    }
}
