<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as AccountSelectTypeStub;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleType;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AclPriviledgeTypeStub;

class AccountUserRoleTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountUserRole';

    /**
     * @var Account
     */
    protected static $accounts;

    /**
     * @var AccountUserRoleType
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

        $this->formType = new AccountUserRoleType();
        $this->formType->setDataClass(self::DATA_CLASS);
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
     * @param array $options
     * @param AccountUserRole|null $defaultData
     * @param AccountUserRole|null $viewData
     * @param array $submittedData
     * @param AccountUserRole|null $expectedData
     * @param array $expectedFieldData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        $defaultData,
        $viewData,
        array $submittedData,
        $expectedData,
        array $expectedFieldData = []
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendUsers'));
        $this->assertTrue($form->has('removeUsers'));

        $formConfig = $form->getConfig();
        $this->assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $actualData = $form->getData();
        $this->assertEquals($expectedData, $actualData);

        if ($defaultData && $defaultData->getRole()) {
            $this->assertEquals($expectedData->getRole(), $actualData->getRole());
        } else {
            $this->assertNotEmpty($actualData->getRole());
        }

        foreach ($expectedFieldData as $field => $data) {
            $this->assertTrue($form->has($field));
            $fieldForm = $form->get($field);
            $this->assertEquals($data, $fieldForm->getData());
        }
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $roleLabel = 'account_role_label';
        $alteredRoleLabel = 'altered_role_label';

        $defaultRole = new AccountUserRole();
        $defaultRole->setLabel($roleLabel);

        /** @var AccountUserRole $existingRoleBefore */
        $existingRoleBefore = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleBefore->setLabel($roleLabel);
        $existingRoleBefore->setRole($roleLabel);

        $existingRoleAfter = clone $existingRoleBefore;
        $existingRoleAfter->setLabel($alteredRoleLabel);

        return [
            'empty' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'label' => $roleLabel,
                ],
                'expectedData' => $defaultRole,
                'expectedFieldData' => [
                    'entity' => [],
                    'action' => [],
                ],
            ],
            'existing' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => $existingRoleBefore,
                'viewData' => $existingRoleBefore,
                'submittedData' => [
                    'label' => $alteredRoleLabel,
                    'entity' => ['first'],
                    'action' => ['second'],
                ],
                'expectedData' => $existingRoleAfter,
                'expectedFieldData' => [
                    'entity' => ['first'],
                    'action' => ['second'],
                ],
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AccountUserRoleType::NAME, $this->formType->getName());
    }

    public function testFinishView()
    {
        $privilegeConfig = ['config'];
        $formView = new FormView();

        $this->formType->finishView(
            $formView,
            $this->getMock('Symfony\Component\Form\FormInterface'),
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
}
