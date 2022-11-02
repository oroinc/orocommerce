<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Form\Type\LateRegistrationType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LateRegistrationTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        $validator->expects($this->any())
            ->method('getMetadataFor')
            ->willReturn(new ClassMetadata(Form::class));

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmit()
    {
        $submittedData =  [
            'is_late_registration_enabled' => true,
            'email' => 'foo@bar.com',
            'password' => [
                'first' => 'Q1foobar',
                'second' => 'Q1foobar'
            ]
        ];

        $form = $this->factory->create(LateRegistrationType::class);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $formData = $form->getData();
        $submittedData['password'] = 'Q1foobar';
        $this->assertEquals($submittedData, $formData);
    }

    public function testIsLateRegistrationEnabledByDefault()
    {
        $expectedData =  [
            'is_late_registration_enabled' => true
        ];

        $form = $this->factory->create(LateRegistrationType::class);
        $formData = $form->getData();
        $this->assertEquals($expectedData, $formData);
    }

    public function testIsLateRegistrationEnabledByDefaultWithNullEmail()
    {
        $form = $this->factory->create(LateRegistrationType::class, ['email' => null]);
        $formData = $form->getData();
        $this->assertEquals(
            [
                'email' => null,
                'is_late_registration_enabled' => true
            ],
            $formData
        );
    }

    public function testSubmitWithUncheckedCheckbox()
    {
        $expectedData =  [
            'is_late_registration_enabled' => false,
            'email' =>  null,
            'password' => null
        ];

        $form = $this->factory->create(LateRegistrationType::class);
        $form->submit([]);
        $formData = $form->getData();

        $this->assertEquals($expectedData, $formData);
    }
}
