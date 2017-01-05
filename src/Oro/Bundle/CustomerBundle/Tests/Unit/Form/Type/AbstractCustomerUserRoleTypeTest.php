<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as AccountSelectTypeStub;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserRoleType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AclPriviledgeTypeStub;

abstract class AbstractCustomerUserRoleTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole';

    /**
     * @var Account
     */
    protected static $accounts;

    /**
     * @var CustomerUserRoleType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $privilegeConfig = [
        'entity' => ['entity' => 'config'],
        'action' => ['action' => 'config'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->createCustomerUserRoleFormTypeAndSetDataClass();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityIdentifierType = new EntityIdentifierType([]);
        $accountSelectType = new AccountSelectTypeStub($this->getAccounts(), AccountSelectType::NAME);

        return [
            new PreloadedExtension(
                [
                    $entityIdentifierType->getName() => $entityIdentifierType,
                    $accountSelectType->getName() => $accountSelectType,
                    'oro_acl_collection' => new PrivilegeCollectionType(),
                    AclPriviledgeTypeStub::NAME => new AclPriviledgeTypeStub(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $roleLabel = 'account_role_label';
        $alteredRoleLabel = 'altered_role_label';

        $defaultRole = new CustomerUserRole();
        $defaultRole->setLabel($roleLabel);

        /** @var CustomerUserRole $existingRoleBefore */
        $existingRoleBefore = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleBefore
            ->setLabel($roleLabel)
            ->setRole($roleLabel, false);

        $existingRoleAfter = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleAfter
            ->setLabel($alteredRoleLabel)
            ->setRole($roleLabel, false);

        return [
            'empty' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'label' => $roleLabel,
                ],
                'expectedData' => $defaultRole,
            ],
            'existing' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => $existingRoleBefore,
                'viewData' => $existingRoleBefore,
                'submittedData' => [
                    'label' => $alteredRoleLabel
                ],
                'expectedData' => $existingRoleAfter,
            ]
        ];
    }

    public function testFinishView()
    {
        $privilegeConfig = ['config'];
        $formView = new FormView();

        $this->formType->finishView(
            $formView,
            $this->createMock('Symfony\Component\Form\FormInterface'),
            ['privilege_config' => $privilegeConfig]
        );

        $this->assertArrayHasKey('privilegeConfig', $formView->vars);
        $this->assertEquals($privilegeConfig, $formView->vars['privilegeConfig']);
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
     * Create form type
     */
    abstract protected function createCustomerUserRoleFormTypeAndSetDataClass();

    /**
     * Make test for testing form type name
     */
    abstract public function testGetName();

    /**
     * @param array $options
     * @param CustomerUserRole|null $defaultData
     * @param CustomerUserRole|null $viewData
     * @param array $submittedData
     * @param CustomerUserRole|null $expectedData
     */
    abstract public function testSubmit(
        array $options,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData
    );
}
