<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

class PriceListTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\PricingBundle\Entity\PriceList';
    const ACCOUNT_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup';
    const WEBSITE_CLASS = 'OroB2B\Bundle\WebsiteBundle\Entity\Website';

    /**
     * @var PriceListType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new PriceListType();
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setAccountClass(self::ACCOUNT_CLASS);
        $this->type->setAccountGroupClass(self::ACCOUNT_GROUP_CLASS);
        $this->type->setWebsiteClass(self::WEBSITE_CLASS);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $currencySelectType = new CurrencySelectionTypeStub();
        $entityIdentifierType = new EntityIdentifierType(
            [
                1 => $this->getEntity(self::ACCOUNT_CLASS, 1),
                2 => $this->getEntity(self::ACCOUNT_CLASS, 2),
                3 => $this->getEntity(self::ACCOUNT_GROUP_CLASS, 3),
                4 => $this->getEntity(self::ACCOUNT_GROUP_CLASS, 4),
                5 => $this->getEntity(self::WEBSITE_CLASS, 5),
                6 => $this->getEntity(self::WEBSITE_CLASS, 6)
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $currencySelectType->getName() => $currencySelectType,
                    $entityIdentifierType->getName() => $entityIdentifierType
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('currencies'));
        $this->assertTrue($form->has('appendAccounts'));
        $this->assertTrue($form->has('removeAccounts'));
        $this->assertTrue($form->has('appendAccountGroups'));
        $this->assertTrue($form->has('removeAccountGroups'));
        $this->assertTrue($form->has('appendWebsites'));
        $this->assertTrue($form->has('removeWebsites'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        if ($defaultData) {
            $existingPriceList = new PriceList();
            $class = new \ReflectionClass($existingPriceList);
            $prop  = $class->getProperty('id');
            $prop->setAccessible(true);

            $prop->setValue($existingPriceList, 42);
            $existingPriceList->setName($defaultData['name']);

            foreach ($defaultData['currencies'] as $currency) {
                $existingPriceList->addCurrencyByCode($currency);
            }

            $defaultData = $existingPriceList;
        }

        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPriceList)) {
            $this->assertEquals($existingPriceList, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var PriceList $result */
        $result = $form->getData();
        $this->assertEquals($expectedData['name'], $result->getName());
        $this->assertEquals($expectedData['currencies'], array_values($result->getCurrencies()));
        $this->assertEquals($expectedData['appendAccounts'], $form->get('appendAccounts')->getData());
        $this->assertEquals($expectedData['removeAccounts'], $form->get('removeAccounts')->getData());
        $this->assertEquals($expectedData['appendAccountGroups'], $form->get('appendAccountGroups')->getData());
        $this->assertEquals($expectedData['removeAccountGroups'], $form->get('removeAccountGroups')->getData());
        $this->assertEquals($expectedData['appendWebsites'], $form->get('appendWebsites')->getData());
        $this->assertEquals($expectedData['removeWebsites'], $form->get('removeWebsites')->getData());
    }
    
    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new price list' => [
                'defaultData' => null,
                'submittedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'expectedData' => [
                    'name' => 'Test Price List',
                    'currencies' => [],
                    'default' => false,
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ]
            ],
            'update price list' => [
                'defaultData' => [
                    'name' => 'Test Price List',
                    'currencies' => ['USD', 'UAH'],
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'submittedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['EUR', 'USD'],
                    'appendAccounts' => [1],
                    'removeAccounts' => [2],
                    'appendAccountGroups' => [3],
                    'removeAccountGroups' => [4],
                    'appendWebsites' => [5],
                    'removeWebsites' => [6],
                ],
                'expectedData' => [
                    'name' => 'Test Price List 01',
                    'currencies' => ['EUR', 'USD'],
                    'appendAccounts' => [$this->getEntity(self::ACCOUNT_CLASS, 1)],
                    'removeAccounts' => [$this->getEntity(self::ACCOUNT_CLASS, 2)],
                    'appendAccountGroups' => [
                        $this->getEntity(self::ACCOUNT_GROUP_CLASS, 3)
                    ],
                    'removeAccountGroups' => [
                        $this->getEntity(self::ACCOUNT_GROUP_CLASS, 4)
                    ],
                    'appendWebsites' => [$this->getEntity(self::WEBSITE_CLASS, 5)],
                    'removeWebsites' => [$this->getEntity(self::WEBSITE_CLASS, 6)],
                ]
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(PriceListType::NAME, $this->type->getName());
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
