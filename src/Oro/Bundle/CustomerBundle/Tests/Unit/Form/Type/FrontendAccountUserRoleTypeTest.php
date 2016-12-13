<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as AccountSelectTypeStub;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserRoleType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AclPriviledgeTypeStub;
use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\FrontendOwnerSelectTypeStub;

class FrontendAccountUserRoleTypeTest extends AbstractAccountUserRoleTypeTest
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityIdentifierType = new EntityIdentifierType([]);
        $accountSelectType = new AccountSelectTypeStub($this->getAccounts(), AccountSelectType::NAME);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();
        return [
            new PreloadedExtension(
                [
                    $entityIdentifierType->getName() => $entityIdentifierType,
                    $accountSelectType->getName() => $accountSelectType,
                    'oro_acl_collection' => new PrivilegeCollectionType(),
                    AclPriviledgeTypeStub::NAME => new AclPriviledgeTypeStub(),
                    FrontendOwnerSelectTypeStub::NAME => new FrontendOwnerSelectTypeStub(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'translatable_entity' => $translatableEntity,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * {@inheritdoc}
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
        $this->assertTrue($form->has('account'));
        $this->assertFalse($form->has('selfManaged'));

        $formConfig = $form->getConfig();
        $this->assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertTrue($formConfig->getOption('hide_self_managed'));

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
        $account = new Account();

        $defaultRole = new AccountUserRole();
        $defaultRole->setLabel($roleLabel);
        $defaultRole->setAccount($account);
        /** @var AccountUserRole $existingRoleBefore */
        $existingRoleBefore = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleBefore
            ->setLabel($roleLabel)
            ->setRole($roleLabel, false)
            ->setAccount($account);

        $existingRoleAfter = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleAfter
            ->setLabel($alteredRoleLabel)
            ->setRole($roleLabel, false)
            ->setAccount($account);

        return [
            'empty' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => $defaultRole,
                'viewData' => $defaultRole,
                'submittedData' => [
                    'label' => $roleLabel,
                    'account' => $defaultRole->getAccount()->getName()
                ],
                'expectedData' => $defaultRole,
                'expectedFieldData' => [
                    'entity' => [],
                    'action' => []
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
                    'account' => $existingRoleBefore->getAccount()->getName()
                ],
                'expectedData' => $existingRoleAfter,
                'expectedFieldData' => [
                    'entity' => ['first'],
                    'action' => ['second'],
                ],
            ]
        ];
    }

    public function testSubmitUpdateAccountUsers()
    {
        /** @var Account $account */
        $account1 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Account', 1);
        $account2 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Account', 2);

        /** @var AccountUserRole $role */
        $role = $this->getEntity(self::DATA_CLASS, 1);
        $role->setRole('label');
        $role->setAccount($account1);

        /** @var AccountUser $accountUser1 */
        $accountUser1 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser', 1);
        $accountUser1->setAccount($account1);

        /** @var AccountUser $accountUser2 */
        $accountUser2 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser', 2);
        $accountUser2->setAccount($account2);

        /** @var AccountUser $accountUser3 */
        $accountUser3 = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountUser', 3);

        /** @var AccountUserRole $predefinedRole */
        $predefinedRole = $this->getEntity(self::DATA_CLASS, 2);
        $role->setRole('predefined');
        $predefinedRole->addAccountUser($accountUser1);
        $predefinedRole->addAccountUser($accountUser2);
        $predefinedRole->addAccountUser($accountUser3);

        $form = $this->factory->create(
            $this->formType,
            $role,
            ['privilege_config' => $this->privilegeConfig, 'predefined_role' => $predefinedRole]
        );

        $this->assertTrue($form->has('appendUsers'));
        $this->assertEquals([$accountUser1], $form->get('appendUsers')->getData());
    }

    /**
     * @inheritdoc
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendAccountUserRoleType::NAME, $this->formType->getName());
    }

    /**
     * @inheritdoc
     */
    protected function createAccountUserRoleFormTypeAndSetDataClass()
    {
        $this->formType = new FrontendAccountUserRoleType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }
}
