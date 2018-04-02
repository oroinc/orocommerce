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
     * @var LateRegistrationType
     */
    private $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new LateRegistrationType();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $validator = $this->createMock(ValidatorInterface::class);

        $validator
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));

        $validator
            ->method('getMetadataFor')
            ->will($this->returnValue(new ClassMetadata(Form::class)));

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

        $form = $this->factory->create($this->formType);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $formData = $form->getData();
        $submittedData['password'] = 'Q1foobar';
        $this->assertEquals($submittedData, $formData);
    }
}
