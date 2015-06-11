<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as CustomerSelectTypeStub;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityType ;

class AccountUserTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AccountUserType
     */
    protected $formType;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

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
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType = new AccountUserType($this->translator);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);

        $customerSelectType = new CustomerSelectTypeStub($this->getCustomers(), 'orob2b_customer_select');

        return [
            new PreloadedExtension(
                [
                    'entity' => $entityType,
                    $customerSelectType->getName() => $customerSelectType
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param $defaultData
     * @param $submittedData
     * @param $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
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
        $newAccountUser = new AccountUser();

        $existingAccountUser = new AccountUser();

        $class = new \ReflectionClass($existingAccountUser);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($existingAccountUser, 42);

        $existingAccountUser->setFirstName('John');
        $existingAccountUser->setLastName('Doe');
        $existingAccountUser->setEmail('johndoe@example.com');
        $existingAccountUser->setPassword('123456');
        $existingAccountUser->setCustomer($this->getCustomer(1));

        $alteredExistingAccountUser = clone $existingAccountUser;
        $alteredExistingAccountUser->setCustomer($this->getCustomer(2));

        return [
            'new user' => [
                'defaultData' => $newAccountUser,
                'submittedData' => [],
                'expectedData' => $newAccountUser
            ],
            'existing user' => [
                'defaultData' => $existingAccountUser,
                'submittedData' => [],
                'expectedData' => $existingAccountUser
            ],
            'altered existing user' => [
                'defaultData' => $existingAccountUser,
                'submittedData' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'johndoe@example.com',
                    'customer' => 2
                ],
                'expectedData' => $alteredExistingAccountUser
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(AccountUserType::NAME, $this->formType->getName());
    }

    /**
     * @return Customer[]
     */
    protected function getCustomers()
    {
        if (!self::$customers) {
            self::$customers = [
                '1' => $this->createCustomer(1, 'first'),
                '2' => $this->createCustomer(2, 'second')
            ];
        }

        return self::$customers;
    }

    /**
     * @param int $id
     * @return Customer
     */
    protected function getCustomer($id)
    {
        $customers = $this->getCustomers();

        return $customers[$id];
    }

    /**
     * @param int $id
     * @param string $name
     * @return Customer
     */
    protected static function createCustomer($id, $name)
    {
        $customer = new Customer();

        $reflection = new \ReflectionProperty(get_class($customer), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($customer, $id);

        $customer->setName($name);

        return $customer;
    }
}
