<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as CustomerSelectTypeStub;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserRoleType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\AclPriviledgeTypeStub;
use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\FrontendOwnerSelectTypeStub;

class FrontendCustomerUserRoleTypeTest extends AbstractCustomerUserRoleTypeTest
{
    /** @var CustomerUser[] */
    protected $customerUsers = [];

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityIdentifierType = new EntityIdentifierType($this->getCustomerUsers());
        $customerSelectType = new CustomerSelectTypeStub($this->getCustomers(), CustomerSelectType::NAME);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();
        return [
            new PreloadedExtension(
                [
                    $entityIdentifierType->getName() => $entityIdentifierType,
                    $customerSelectType->getName() => $customerSelectType,
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
        $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendUsers'));
        $this->assertTrue($form->has('removeUsers'));
        $this->assertTrue($form->has('customer'));
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
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $roleLabel = 'customer_role_label';
        $alteredRoleLabel = 'altered_role_label';
        $customer = new Customer();

        $defaultRole = new CustomerUserRole();
        $defaultRole->setLabel($roleLabel);
        $defaultRole->setCustomer($customer);
        /** @var CustomerUserRole $existingRoleBefore */
        $existingRoleBefore = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleBefore
            ->setLabel($roleLabel)
            ->setRole($roleLabel, false)
            ->setCustomer($customer);

        $existingRoleAfter = $this->getEntity(self::DATA_CLASS, 1);
        $existingRoleAfter
            ->setLabel($alteredRoleLabel)
            ->setRole($roleLabel, false)
            ->setCustomer($customer);

        return [
            'empty' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => $defaultRole,
                'viewData' => $defaultRole,
                'submittedData' => [
                    'label' => $roleLabel,
                    'customer' => $defaultRole->getCustomer()->getName()
                ],
                'expectedData' => $defaultRole
            ],
            'existing' => [
                'options' => ['privilege_config' => $this->privilegeConfig],
                'defaultData' => $existingRoleBefore,
                'viewData' => $existingRoleBefore,
                'submittedData' => [
                    'label' => $alteredRoleLabel,
                    'customer' => $existingRoleBefore->getCustomer()->getName()
                ],
                'expectedData' => $existingRoleAfter
            ]
        ];
    }

    public function testSubmitUpdateCustomerUsers()
    {
        list($customer1, $customer2) = array_values($this->getCustomers());
        list($customerUser1, $customerUser2, $customerUser3) = array_values($this->getCustomerUsers());

        /** @var CustomerUserRole $role */
        $role = $this->getEntity(self::DATA_CLASS, 1);
        $role->setRole('label');
        $role->setCustomer($customer1);

        /** @var CustomerUserRole $predefinedRole */
        $predefinedRole = $this->getEntity(self::DATA_CLASS, 2);
        $role->setRole('predefined');
        $predefinedRole->addCustomerUser($customerUser1);
        $predefinedRole->addCustomerUser($customerUser2);
        $predefinedRole->addCustomerUser($customerUser3);

        $form = $this->factory->create(
            $this->formType,
            $role,
            ['privilege_config' => $this->privilegeConfig, 'predefined_role' => $predefinedRole]
        );

        $this->assertTrue($form->has('appendUsers'));
        $this->assertEquals([$customerUser1], $form->get('appendUsers')->getData());

        $form->get('appendUsers')->setData([$customerUser1, $customerUser2]);
        $form->get('removeUsers')->setData([$customerUser1]);
        $form->submit([
            'label' => 'test label',
            'customer' => new Customer(),
            'appendUsers' => [1, 2],
            'removeUsers' => [1],
        ]);

        $this->assertTrue($predefinedRole->getCustomerUsers()->contains($customerUser1));
        $this->assertFalse($predefinedRole->getCustomerUsers()->contains($customerUser2));
        $this->assertTrue($predefinedRole->getCustomerUsers()->contains($customerUser3));
    }

    /**
     * @inheritdoc
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendCustomerUserRoleType::NAME, $this->formType->getName());
    }

    /**
     * @inheritdoc
     */
    protected function createCustomerUserRoleFormTypeAndSetDataClass()
    {
        $this->formType = new FrontendCustomerUserRoleType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    /**
     * @return CustomerUser[]
     */
    protected function getCustomerUsers()
    {
        if (!$this->customerUsers) {
            list($customer1, $customer2) = array_values($this->getCustomers());

            /** @var CustomerUser $customerUser1 */
            $customerUser1 = $this->getEntity(CustomerUser::class, 1);
            $customerUser1->setCustomer($customer1);

            /** @var CustomerUser $customerUser2 */
            $customerUser2 = $this->getEntity(CustomerUser::class, 2);
            $customerUser2->setCustomer($customer2);

            /** @var CustomerUser $customerUser3 */
            $customerUser3 = $this->getEntity(CustomerUser::class, 3);

            $this->customerUsers = [
                $customerUser1->getId() => $customerUser1,
                $customerUser2->getId() => $customerUser2,
                $customerUser3->getId() => $customerUser3,
            ];
        }

        return $this->customerUsers;
    }
}
