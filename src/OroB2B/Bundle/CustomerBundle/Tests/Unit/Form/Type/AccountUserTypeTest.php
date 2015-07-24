<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as CustomerSelectTypeStub;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityType ;
use OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressCollectionTypeStub;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CustomerBundle\Entity\AccountUser';
    const ROLE_CLASS = 'OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole';
    const ADDRESS_CLASS = 'OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress';

    /**
     * @var AccountUserType
     */
    protected $formType;

    /** @var \Oro\Bundle\SecurityBundle\SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    private $securityFacade;

    /**
     * @var Customer[]
     */
    protected static $customers = [];

    /**
     * @var AccountUserAddress[]
     */
    protected static $addresses = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new AccountUserType($this->securityFacade);
        $this->formType->setDataClass(self::DATA_CLASS);
        $this->formType->setRoleClass(self::ROLE_CLASS);
        $this->formType->setAddressClass(self::ADDRESS_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $rolesEntity = new EntityType(
            [
                1 => $this->getRole(1, 'test01'),
                2 => $this->getRole(2, 'test02')
            ]
        );

        $addressEntityType = new EntityType($this->getAddresses(), 'test_address_entity');
        $customerSelectType = new CustomerSelectTypeStub($this->getCustomers(), 'orob2b_customer_select');

        return [
            new PreloadedExtension(
                [
                    OroDateType::NAME => new OroDateType(),
                    'entity' => $rolesEntity,
                    $customerSelectType->getName() => $customerSelectType,
                    'oro_address_collection'  => new AddressCollectionTypeStub(),
                    $addressEntityType->getName()  => $addressEntityType,
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
     * @param bool $rolesGranted
     * @dataProvider submitProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, $rolesGranted = true)
    {
        if ($rolesGranted) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with('orob2b_customer_account_user_role_view')
                ->will($this->returnValue(true));
        }

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
        $existingAccountUser->addAddress($this->getAddresses()[1]);

        $alteredExistingAccountUser = clone $existingAccountUser;
        $alteredExistingAccountUser->setEnabled(false);
        $alteredExistingAccountUser->setCustomer($this->getCustomer(2));

        $alteredExistingAccountUserWithRole = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithRole->setRoles([$this->getRole(2, 'test02')]);

        $alteredExistingAccountUserWithAddresses = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithAddresses->addAddress($this->getAddresses()[2]);

        return [
            'user without submitted data' => [
                'defaultData' => $newAccountUser,
                'submittedData' => [],
                'expectedData' => $newAccountUser
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
            ],
            'altered existing user with roles' => [
                'defaultData' => $existingAccountUser,
                'submittedData' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'johndoe@example.com',
                    'customer' => 2,
                    'roles' => [2]
                ],
                'expectedData' => $alteredExistingAccountUserWithRole,
                'rolesGranted' => true
            ],
            'altered existing user with addresses' => [
                'defaultData' => $existingAccountUser,
                'submittedData' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'johndoe@example.com',
                    'customer' => 2,
                    'addresses' => [1, 2]
                ],
                'expectedData' => $alteredExistingAccountUserWithAddresses,
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
     * @return AccountUserAddress[]
     */
    protected function getAddresses()
    {
        if (!self::$addresses) {
            self::$addresses = [
                1 => $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress', 1),
                2 => $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress', 2)
            ];
        }

        return self::$addresses;
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

    /**
     * @param int $id
     * @param string $label
     * @return object
     */
    protected function getRole($id, $label)
    {
        $role = new AccountUserRole($label);

        $reflection = new \ReflectionProperty(get_class($role), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($role, $id);

        $role->setLabel($label);

        return $role;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
