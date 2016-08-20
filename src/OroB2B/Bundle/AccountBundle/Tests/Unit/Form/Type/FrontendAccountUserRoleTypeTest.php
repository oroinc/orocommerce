<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserRole;
use Oro\Bundle\AccountBundle\Form\Type\FrontendAccountUserRoleType;

class FrontendAccountUserRoleTypeTest extends AbstractAccountUserRoleTypeTest
{
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
        $this->assertFalse($form->has('account'));

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

    public function testSubmitUpdateAccountUsers()
    {
        /** @var Account $account */
        $account1 = $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 1);
        $account2 = $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', 2);

        /** @var AccountUserRole $role */
        $role = $this->getEntity(self::DATA_CLASS, 1);
        $role->setRole('label');
        $role->setAccount($account1);

        /** @var AccountUser $accountUser1 */
        $accountUser1 = $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', 1);
        $accountUser1->setAccount($account1);

        /** @var AccountUser $accountUser2 */
        $accountUser2 = $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', 2);
        $accountUser2->setAccount($account2);

        /** @var AccountUser $accountUser3 */
        $accountUser3 = $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', 3);

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
