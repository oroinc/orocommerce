<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;

use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserRoleType;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserRoleTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AccountUserRoleType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AccountUserRoleType();
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

        return [
            new PreloadedExtension(
                [
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        $defaultData,
        $viewData,
        $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertTrue($form->has('appendUsers'));
        $this->assertTrue($form->has('removeUsers'));

        $formConfig = $form->getConfig();
        $this->assertEquals(
            'OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole',
            $formConfig->getOption('data_class')
        );

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $roleLabel = 'customer_role_label';
        $alteredRoleLabel = 'altered_role_label';

        $defaultRole = new AccountUserRole();
        $defaultRole->setLabel($roleLabel);

        /** @var AccountUserRole $existingRoleBefore */
        $existingRoleBefore = $this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole', 1);
        $existingRoleBefore->setLabel($roleLabel);

        $existingRoleAfter = clone $existingRoleBefore;
        $existingRoleAfter->setLabel($alteredRoleLabel);

        return [
            'empty' => [
                'options' => [],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'label' => $roleLabel,
                ],
                'expectedData' => $defaultRole
            ],
            'existing' => [
                'options' => [],
                'defaultData' => $existingRoleBefore,
                'viewData' => $existingRoleBefore,
                'submittedData' => [
                    'label' => $alteredRoleLabel,
                ],
                'expectedData' => $existingRoleAfter
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AccountUserRoleType::NAME, $this->formType->getName());
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
