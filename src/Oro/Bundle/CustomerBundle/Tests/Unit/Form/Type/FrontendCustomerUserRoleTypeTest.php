<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
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
     * @var FrontendCustomerUserRoleType
     */
    protected $formType;

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

    /**
     * @dataProvider preSubmitProvider
     * @param array $data
     * @param array $expected
     */
    public function testPreSubmit(array $data, array $expected)
    {
        $event = new FormEvent($this->prepareFormForEvents(), $data);

        $this->formType->preSubmit($event);

        $this->assertEquals($expected, $event->getData());
    }

    /**
     * @return array
     */
    public function preSubmitProvider()
    {
        return [
            'append and remove users are empty' => [
                'data' => [
                    'customer' => '1',
                    'appendUsers' => '',
                    'removeUsers' => '',
                ],
                'expected' => [
                    'customer' => '1',
                    'appendUsers' => '1,4',
                    'removeUsers' => '',
                ]

            ],
            'append new user and remove one from predifined role' => [
                'data' => [
                    'customer' => '1',
                    'appendUsers' => '2',
                    'removeUsers' => '4',
                ],
                'expected' => [
                    'customer' => '1',
                    'appendUsers' => '1,2',
                    'removeUsers' => '4',
                ]

            ]
        ];
    }

    public function testPostSubmit()
    {
        list($customerUser1, , , $customerUser4) = array_values($this->getCustomerUsers());
        list($customer1) = array_values($this->getCustomers());

        $form = $this->prepareFormForEvents();
        $form->get('appendUsers')->setData([$customerUser1]);
        $form->get('removeUsers')->setData([$customerUser4]);

        $role = $this->getEntity(self::DATA_CLASS, 1);
        $role->setCustomer($customer1);

        $event = new FormEvent($form, $role);

        $this->formType->postSubmit($event);

        $predefinedRole = $form->getConfig()->getOption('predefined_role');
        $this->assertTrue($predefinedRole->getCustomerUsers()->contains($customerUser4));
        $this->assertFalse($predefinedRole->getCustomerUsers()->contains($customerUser1));
    }

    /**
     * {@inheritdoc}
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendCustomerUserRoleType::NAME, $this->formType->getName());
    }

    /**
     * {@inheritdoc}
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

            /** @var CustomerUser $customerUser4 */
            $customerUser4 = $this->getEntity(CustomerUser::class, 4);
            $customerUser4->setCustomer($customer1);

            $this->customerUsers = [
                $customerUser1->getId() => $customerUser1,
                $customerUser2->getId() => $customerUser2,
                $customerUser3->getId() => $customerUser3,
                $customerUser4->getId() => $customerUser4,
            ];
        }

        return $this->customerUsers;
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function prepareFormForEvents()
    {
        list($customerUser1, $customerUser2, $customerUser3, $customerUser4) = array_values($this->getCustomerUsers());

        $role = $this->getEntity(self::DATA_CLASS, 1);
        $predefinedRole = $this->getEntity(self::DATA_CLASS, 2);
        $predefinedRole->addCustomerUser($customerUser1);
        $predefinedRole->addCustomerUser($customerUser2);
        $predefinedRole->addCustomerUser($customerUser3);
        $predefinedRole->addCustomerUser($customerUser4);

        $form = $this->factory->create(
            $this->formType,
            $role,
            ['privilege_config' => $this->privilegeConfig, 'predefined_role' => $predefinedRole]
        );

        return $form;
    }
}
