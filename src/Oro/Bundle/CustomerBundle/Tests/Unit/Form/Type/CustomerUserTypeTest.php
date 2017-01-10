<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserRoleSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressCollectionTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntitySelectTypeStub;

class CustomerUserTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUser';
    const ROLE_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole';
    const ADDRESS_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress';

    /**
     * @var CustomerUserType
     */
    protected $formType;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var Customer[]
     */
    protected static $customers = [];

    /**
     * @var CustomerUserAddress[]
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

        $this->formType = new CustomerUserType($this->securityFacade);
        $this->formType->setDataClass(self::DATA_CLASS);
        $this->formType->setAddressClass(self::ADDRESS_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $customerUserRoleSelectType = new EntitySelectTypeStub(
            $this->getRoles(),
            CustomerUserRoleSelectType::NAME,
            new CustomerUserRoleSelectType($this->createTranslator())
        );
        $addressEntityType = new EntityType($this->getAddresses(), 'test_address_entity');
        $customerSelectType = new EntityType($this->getCustomers(), CustomerSelectType::NAME);

        $userMultiSelectType = new EntityType(
            [
                1 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1),
                2 => $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2),
            ],
            UserMultiSelectType::NAME,
            [
                'multiple' => true,
            ]
        );

        return [
            new PreloadedExtension(
                [
                    OroDateType::NAME => new OroDateType(),
                    CustomerUserRoleSelectType::NAME => $customerUserRoleSelectType,
                    $customerSelectType->getName() => $customerSelectType,
                    AddressCollectionTypeStub::NAME => new AddressCollectionTypeStub(),
                    $addressEntityType->getName() => $addressEntityType,
                    $userMultiSelectType->getName() => $userMultiSelectType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param CustomerUser $defaultData
     * @param array $submittedData
     * @param CustomerUser $expectedData
     * @param bool|true $rolesGranted
     */
    public function testSubmit(
        CustomerUser $defaultData,
        array $submittedData,
        CustomerUser $expectedData,
        $rolesGranted = true
    ) {
        if ($rolesGranted) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with('oro_customer_customer_user_role_view')
                ->will($this->returnValue(true));
        }
        $this->securityFacade->expects($this->exactly(2))->method('getOrganization')->willReturn(new Organization());

        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertTrue($form->has('roles'));
        $options = $form->get('roles')->getConfig()->getOptions();
        $this->assertArrayHasKey('query_builder', $options);
        $this->assertQueryBuilderCallback($options['query_builder']);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());

        $this->assertTrue($form->has('roles'));
        $options = $form->get('roles')->getConfig()->getOptions();
        $this->assertArrayHasKey('query_builder', $options);
        $this->assertQueryBuilderCallback($options['query_builder']);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $newCustomerUser = $this->createCustomerUser();

        $existingCustomerUser = clone $newCustomerUser;

        $class = new \ReflectionClass($existingCustomerUser);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($existingCustomerUser, 42);

        $existingCustomerUser->setFirstName('Mary');
        $existingCustomerUser->setLastName('Doe');
        $existingCustomerUser->setEmail('john@example.com');
        $existingCustomerUser->setPassword('123456');
        $existingCustomerUser->setCustomer($this->getCustomer(1));
        $existingCustomerUser->addAddress($this->getAddresses()[1]);
        $existingCustomerUser->setOrganization(new Organization());
        $existingCustomerUser->addSalesRepresentative($this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1));

        $alteredExistingCustomerUser = clone $existingCustomerUser;
        $alteredExistingCustomerUser->setCustomer($this->getCustomer(2));
        $alteredExistingCustomerUser->setEnabled(false);

        $alteredExistingCustomerUserWithRole = clone $alteredExistingCustomerUser;
        $alteredExistingCustomerUserWithRole->setRoles([$this->getRole(2, 'test02')]);

        $alteredExistingCustomerUserWithAddresses = clone $alteredExistingCustomerUser;
        $alteredExistingCustomerUserWithAddresses->addAddress($this->getAddresses()[2]);

        $alteredExistingCustomerUserWithSalesRepresentatives = clone $alteredExistingCustomerUser;
        $alteredExistingCustomerUserWithSalesRepresentatives->addSalesRepresentative(
            $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2)
        );

        return
            [
                'user without submitted data' => [
                    'defaultData' => $newCustomerUser,
                    'submittedData' => [],
                    'expectedData' => $newCustomerUser
                ],
                'altered existing user' => [
                    'defaultData' => $existingCustomerUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'customer' => 2
                    ],
                    'expectedData' => $alteredExistingCustomerUser
                ],
                'altered existing user with roles' => [
                    'defaultData' => $existingCustomerUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'customer' => 2,
                        'roles' => [2]
                    ],
                    'expectedData' => $alteredExistingCustomerUserWithRole,
                    'rolesGranted' => true
                ],
                'altered existing user with addresses' => [
                    'defaultData' => $existingCustomerUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'customer' => 2,
                        'addresses' => [1, 2]
                    ],
                    'expectedData' => $alteredExistingCustomerUserWithAddresses,
                ],
                'altered existing user with salesRepresentatives' => [
                    'defaultData' => $existingCustomerUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'customer' => 2,
                        'salesRepresentatives' => [],
                    ],
                    'expectedData' => $alteredExistingCustomerUserWithSalesRepresentatives,
                ],
            ];
    }

    /**
     * @param \Closure $callable
     */
    protected function assertQueryBuilderCallback($callable)
    {
        $this->assertInternalType('callable', $callable);

        $repository = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())->method('getAvailableRolesByCustomerUserQueryBuilder');

        $callable($repository);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(CustomerUserType::NAME, $this->formType->getName());
    }

    /**
     * @return CustomerUserAddress[]
     */
    protected function getAddresses()
    {
        if (!self::$addresses) {
            self::$addresses = [
                1 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 1),
                2 => $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress', 2)
            ];
        }

        return self::$addresses;
    }

    /**
     * @return CustomerUserRole[]
     */
    protected function getRoles()
    {
        return [
            1 => $this->getRole(1, 'test01'),
            2 => $this->getRole(2, 'test02')
        ];
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
     * @return CustomerUserRole
     */
    protected function getRole($id, $label)
    {
        $role = new CustomerUserRole($label);

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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function createTranslator()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message . '.trans';
                }
            );

        return $translator;
    }

    /**
     * @return CustomerUser
     */
    private function createCustomerUser()
    {
        $customerUser = new CustomerUser();
        $customerUser->setOrganization(new Organization());

        return $customerUser;
    }
}
