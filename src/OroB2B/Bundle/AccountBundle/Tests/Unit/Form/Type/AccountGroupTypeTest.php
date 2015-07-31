<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup';
    const ACCOUNT_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';

    /**
     * @var AccountGroupType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AccountGroupType();
        $this->formType->setDataClass(self::DATA_CLASS);
        $this->formType->setAccountClass(self::ACCOUNT_CLASS);
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

        $this->assertTrue($form->has('appendAccounts'));
        $this->assertTrue($form->has('removeAccounts'));

        $formConfig = $form->getConfig();
        $this->assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

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
        $groupName = 'account_group_name';
        $alteredGroupName = 'altered_group_name';

        $defaultGroup = new AccountGroup();
        $defaultGroup->setName($groupName);

        /** @var AccountGroup $existingGroupBefore */
        $existingGroupBefore = $this->getEntity(self::DATA_CLASS, 1);
        $existingGroupBefore->setName($groupName);

        $existingGroupAfter = clone $existingGroupBefore;
        $existingGroupAfter->setName($alteredGroupName);

        return [
            'empty' => [
                'options' => [],
                'defaultData' => null,
                'viewData' => null,
                'submittedData' => [
                    'name' => $groupName,
                ],
                'expectedData' => $defaultGroup
            ],
            'existing' => [
                'options' => [],
                'defaultData' => $existingGroupBefore,
                'viewData' => $existingGroupBefore,
                'submittedData' => [
                    'name' => $alteredGroupName,
                ],
                'expectedData' => $existingGroupAfter
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AccountGroupType::NAME, $this->formType->getName());
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
