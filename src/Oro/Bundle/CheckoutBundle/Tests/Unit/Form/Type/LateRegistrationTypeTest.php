<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Form\Type\LateRegistrationType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Validator\Constraints\UniqueCustomerUserNameAndEmailValidator;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexityValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

class LateRegistrationTypeTest extends FormIntegrationTestCase
{
    #[\Override]
    protected function getExtensions(): array
    {
        $customerUserManager = $this->createMock(CustomerUserManager::class);
        $passwordComplexityConfigProvider = $this->createMock(PasswordComplexityConfigProvider::class);
        $constraintValidatorFactoryContainer = TestContainerBuilder::create()
            ->add(
                'oro_customer.customer_user.validator.unique_name_and_email',
                new UniqueCustomerUserNameAndEmailValidator($customerUserManager)
            )
            ->add(
                PasswordComplexityValidator::class,
                new PasswordComplexityValidator($passwordComplexityConfigProvider)
            )
            ->getContainer($this);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(
                new ContainerConstraintValidatorFactory($constraintValidatorFactoryContainer)
            )
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmit(): void
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

    public function testSubmitWithInvalidEmail(): void
    {
        $submittedData =  [
            'is_late_registration_enabled' => true,
            'email' => 'f o o@bar.com',
            'password' => [
                'first' => 'Q1foobar',
                'second' => 'Q1foobar'
            ]
        ];

        $form = $this->factory->create(LateRegistrationType::class);

        $form->submit($submittedData);

        self::assertTrue($form->isSynchronized());
        self::assertFormIsNotValid($form);

        $errors = $form->getErrors(true);
        self::assertGreaterThan(0, $errors->count());
        self::assertStringContainsString(
            'This value is not a valid email address.',
            (string) $errors
        );

        $formData = $form->getData();
        $submittedData['password'] = 'Q1foobar';
        self::assertEquals($submittedData, $formData);
    }

    public function testIsLateRegistrationEnabledByDefault(): void
    {
        $expectedData =  [
            'is_late_registration_enabled' => true
        ];

        $form = $this->factory->create(LateRegistrationType::class);
        $formData = $form->getData();
        $this->assertEquals($expectedData, $formData);
    }

    public function testIsLateRegistrationEnabledByDefaultWithNullEmail(): void
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

    public function testSubmitWithUncheckedCheckbox(): void
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
