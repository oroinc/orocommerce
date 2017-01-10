<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\ChangePasswordTypeStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserProfileType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\FrontendOwnerSelectTypeStub;

class FrontendCustomerUserProfileTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendCustomerUserProfileType
     */
    protected $formType;

    /**
     * @var Customer[]
     */
    protected static $customers = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FrontendCustomerUserProfileType();
        $this->formType->setDataClass('Oro\Bundle\CustomerBundle\Entity\CustomerUser');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset($this->formType);
        self::$customers = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroDateType::NAME => new OroDateType(),
                    FrontendOwnerSelectTypeStub::NAME => new FrontendOwnerSelectTypeStub(),
                    ChangePasswordTypeStub::NAME => new ChangePasswordTypeStub()
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param CustomerUser $defaultData
     * @param array $submittedData
     * @param CustomerUser $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $entity = new CustomerUser();
        $customer = new Customer();
        $entity->setCustomer($customer);
        $existingEntity = new CustomerUser();
        $this->setPropertyValue($existingEntity, 'id', 42);

        $existingEntity->setFirstName('John');
        $existingEntity->setLastName('Doe');
        $existingEntity->setEmail('johndoe@example.com');
        $existingEntity->setPassword('123456');
        $existingEntity->setCustomer($customer);

        $updatedEntity = clone $existingEntity;
        $updatedEntity->setFirstName('John UP');
        $updatedEntity->setLastName('Doe UP');
        $updatedEntity->setEmail('johndoe_up@example.com');

        return [
            'new user' => [
                'defaultData' => $entity,
                'submittedData' => [],
                'expectedData' => $entity
            ],
            'updated user' => [
                'defaultData' => $existingEntity,
                'submittedData' => [
                    'firstName' => $updatedEntity->getFirstName(),
                    'lastName' => $updatedEntity->getLastName(),
                    'email' => $updatedEntity->getEmail(),
                    'customer' => $updatedEntity->getCustomer()->getName(),
                ],
                'expectedData' => $updatedEntity
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendCustomerUserProfileType::NAME, $this->formType->getName());
    }

    /**
     * @param CustomerUser $existingCustomerUser
     * @param string $property
     * @param mixed $value
     */
    protected function setPropertyValue(CustomerUser $existingCustomerUser, $property, $value)
    {
        $class = new \ReflectionClass($existingCustomerUser);
        $prop = $class->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($existingCustomerUser, $value);
    }
}
