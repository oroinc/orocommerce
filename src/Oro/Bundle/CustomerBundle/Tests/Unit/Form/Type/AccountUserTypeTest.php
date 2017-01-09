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
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserRoleSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AddressCollectionTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntitySelectTypeStub;

class AccountUserTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUser';
    const ROLE_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole';
    const ADDRESS_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress';

    /**
     * @var AccountUserType
     */
    protected $formType;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var Account[]
     */
    protected static $accounts = [];

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

        $this->formType = new AccountUserType($this->securityFacade);
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
        $accountSelectType = new EntityType($this->getAccounts(), AccountSelectType::NAME);

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
                    $accountSelectType->getName() => $accountSelectType,
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
                ->with('oro_account_customer_user_role_view')
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
        $newAccountUser = $this->createAccountUser();

        $existingAccountUser = clone $newAccountUser;

        $class = new \ReflectionClass($existingAccountUser);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($existingAccountUser, 42);

        $existingAccountUser->setFirstName('Mary');
        $existingAccountUser->setLastName('Doe');
        $existingAccountUser->setEmail('john@example.com');
        $existingAccountUser->setPassword('123456');
        $existingAccountUser->setAccount($this->getAccount(1));
        $existingAccountUser->addAddress($this->getAddresses()[1]);
        $existingAccountUser->setOrganization(new Organization());
        $existingAccountUser->addSalesRepresentative($this->getEntity('Oro\Bundle\UserBundle\Entity\User', 1));

        $alteredExistingAccountUser = clone $existingAccountUser;
        $alteredExistingAccountUser->setAccount($this->getAccount(2));
        $alteredExistingAccountUser->setEnabled(false);

        $alteredExistingAccountUserWithRole = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithRole->setRoles([$this->getRole(2, 'test02')]);

        $alteredExistingAccountUserWithAddresses = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithAddresses->addAddress($this->getAddresses()[2]);

        $alteredExistingAccountUserWithSalesRepresentatives = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithSalesRepresentatives->addSalesRepresentative(
            $this->getEntity('Oro\Bundle\UserBundle\Entity\User', 2)
        );

        return
            [
                'user without submitted data' => [
                    'defaultData' => $newAccountUser,
                    'submittedData' => [],
                    'expectedData' => $newAccountUser
                ],
                'altered existing user' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2
                    ],
                    'expectedData' => $alteredExistingAccountUser
                ],
                'altered existing user with roles' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2,
                        'roles' => [2]
                    ],
                    'expectedData' => $alteredExistingAccountUserWithRole,
                    'rolesGranted' => true
                ],
                'altered existing user with addresses' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2,
                        'addresses' => [1, 2]
                    ],
                    'expectedData' => $alteredExistingAccountUserWithAddresses,
                ],
                'altered existing user with salesRepresentatives' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2,
                        'salesRepresentatives' => [],
                    ],
                    'expectedData' => $alteredExistingAccountUserWithSalesRepresentatives,
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
        $repository->expects($this->once())->method('getAvailableRolesByAccountUserQueryBuilder');

        $callable($repository);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(AccountUserType::NAME, $this->formType->getName());
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
     * @return Account[]
     */
    protected function getAccounts()
    {
        if (!self::$accounts) {
            self::$accounts = [
                '1' => $this->createAccount(1, 'first'),
                '2' => $this->createAccount(2, 'second')
            ];
        }

        return self::$accounts;
    }

    /**
     * @param int $id
     * @return Account
     */
    protected function getAccount($id)
    {
        $accounts = $this->getAccounts();

        return $accounts[$id];
    }

    /**
     * @param int $id
     * @param string $name
     * @return Account
     */
    protected static function createAccount($id, $name)
    {
        $account = new Account();

        $reflection = new \ReflectionProperty(get_class($account), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($account, $id);

        $account->setName($name);

        return $account;
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
    private function createAccountUser()
    {
        $accountUser = new CustomerUser();
        $accountUser->setOrganization(new Organization());

        return $accountUser;
    }
}
