<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\Form\Type\Stub\EnumSelectType;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\ParentAccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AddressCollectionTypeStub;

class AccountTypeTest extends FormIntegrationTestCase
{
    /** @var AccountType */
    protected $formType;

    /** @var EntityManager */
    protected $entityManager;

    /** @var AccountAddress[] */
    protected static $addresses;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AccountType();
        $this->formType->setAddressClass('OroB2B\Bundle\AccountBundle\Entity\AccountAddress');
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
        $accountGroupSelectType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 2)
            ],
            AccountGroupSelectType::NAME
        );

        $parentAccountSelectType = new EntityType(
            [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2)
            ],
            ParentAccountSelectType::NAME
        );

        $addressEntityType = new EntityType($this->getAddresses(), 'test_address_entity');

        $internalRatingEnumSelect = new EnumSelectType(
            [
                new StubEnumValue('1_of_5', '1 of 5'),
                new StubEnumValue('2_of_5', '2 of 5')
            ]
        );

        return [
            new PreloadedExtension(
                [
                    AccountGroupSelectType::NAME  => $accountGroupSelectType,
                    ParentAccountSelectType::NAME => $parentAccountSelectType,
                    'oro_address_collection'  => new AddressCollectionTypeStub(),
                    $addressEntityType->getName()  => $addressEntityType,
                    EnumSelectType::NAME => $internalRatingEnumSelect
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param array $defaultData
     * @param array $viewData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(
        array $options,
        array $defaultData,
        array $viewData,
        array $submittedData,
        array $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        return [
            'default' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'account_name',
                    'group' => 1,
                    'parent' => 2,
                    'addresses' => [1],
                    'internal_rating' => '2_of_5'
                ],
                'expectedData' => [
                    'name' => 'account_name',
                    'group' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1),
                    'parent' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
                    'addresses' => [$this->getAddresses()[1]],
                    'internal_rating' => new StubEnumValue('2_of_5', '2 of 5')
                ]
            ],
            'empty parent' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'account_name',
                    'group' => 1,
                    'parent' => null,
                    'addresses' => [1],
                    'internal_rating' => '2_of_5'
                ],
                'expectedData' => [
                    'name' => 'account_name',
                    'group' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1),
                    'parent' => null,
                    'addresses' => [$this->getAddresses()[1]],
                    'internal_rating' => new StubEnumValue('2_of_5', '2 of 5')
                ]
            ],
            'empty group' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'account_name',
                    'group' => null,
                    'parent' => 2,
                    'addresses' => [1],
                    'internal_rating' => '2_of_5'
                ],
                'expectedData' => [
                    'name' => 'account_name',
                    'group' => null,
                    'parent' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
                    'addresses' => [$this->getAddresses()[1]],
                    'internal_rating' => new StubEnumValue('2_of_5', '2 of 5')
                ]
            ],
            'empty address' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'account_name',
                    'group' => 1,
                    'parent' => 2,
                    'addresses' => null,
                    'internal_rating' => '2_of_5'
                ],
                'expectedData' => [
                    'name' => 'account_name',
                    'group' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1),
                    'parent' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
                    'addresses' => [],
                    'internal_rating' => new StubEnumValue('2_of_5', '2 of 5')
                ]
            ],
            'empty internal_rating' => [
                'options' => [],
                'defaultData' => [],
                'viewData' => [],
                'submittedData' => [
                    'name' => 'account_name',
                    'group' => 1,
                    'parent' => 2,
                    'internal_rating' => null
                ],
                'expectedData' => [
                    'name' => 'account_name',
                    'group' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1),
                    'parent' => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2),
                    'internal_rating' => null,
                    'addresses' => [],
                ]
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->formType->getName());
        $this->assertEquals('orob2b_account_type', $this->formType->getName());
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
     * @return AccountAddress[]
     */
    protected function getAddresses()
    {
        if (!self::$addresses) {
            self::$addresses = [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountAddress', 2)
            ];
        }
        return self::$addresses;
    }
}
