<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PricingBundle\Form\Type\PriceListScheduleType;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleType;

class PriceListTypeTest extends FormIntegrationTestCase
{
    const ACCOUNT_CLASS = 'Oro\Bundle\AccountBundle\Entity\Account';
    const ACCOUNT_GROUP_CLASS = 'Oro\Bundle\AccountBundle\Entity\AccountGroup';
    const WEBSITE_CLASS = 'Oro\Bundle\WebsiteBundle\Entity\Website';

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

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder(CurrencyConfigManager::class)->disableOriginalConstructor()->getMock();
        $configManager->method('getCurrencyList')->willReturn(['USD', 'EUR']);
        $configManager->method('getDefaultCurrency')->willReturn('USD');

        /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $currencyNameHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();


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
                    $entityIdentifierType->getName() => $entityIdentifierType,
                    CollectionType::NAME => new CollectionType(),
                    PriceListScheduleType::NAME => new PriceListScheduleType(new PropertyAccessor()),
                    OroDateTimeType::NAME => new OroDateTimeType(),
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $configManager,
                        $localeSettings,
                        $currencyNameHelper
                    ),
                    'entity' => new EntityType(['item' => (new ProductUnit())->setCode('item')]),
                    PriceRuleType::NAME => new PriceRuleType()
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
            $prop = $class->getProperty('id');
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
        $this->assertSchedules($expectedData, $result);
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
                    'active' => true,
                    'currencies' => [],
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                    'schedules' => [
                        ['activeAt' => '2016-03-01T22:00:00Z', 'deactivateAt' => '2016-03-15T22:00:00Z'],
                        ['activeAt' => '2016-02-01T22:00:00Z', 'deactivateAt' => '2016-02-15T22:00:00Z']
                    ]
                ],
                'expectedData' => [
                    'name' => 'Test Price List',
                    'active' => false,
                    'currencies' => [],
                    'default' => false,
                    'appendAccounts' => [],
                    'removeAccounts' => [],
                    'appendAccountGroups' => [],
                    'removeAccountGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                    'schedules' => [
                        ['2016-03-01T22:00:00Z', '2016-03-15T22:00:00Z'],
                        ['2016-02-01T22:00:00Z', '2016-02-15T22:00:00Z'],
                    ]
                ]
            ],
            'update price list' => [
                'defaultData' => [
                    'name' => 'Test Price List',
                    'active' => true,
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
                    'active' => false,
                    'currencies' => ['EUR', 'USD'],
                    'appendAccounts' => [1],
                    'removeAccounts' => [2],
                    'appendAccountGroups' => [3],
                    'removeAccountGroups' => [4],
                    'appendWebsites' => [5],
                    'removeWebsites' => [6],
                    'schedules' => [
                        ['activeAt' => '2016-03-01T22:00:00Z', 'deactivateAt' => '2016-03-15T22:00:00Z'],
                        ['activeAt' => '2016-02-01T22:00:00Z', 'deactivateAt' => '2016-02-15T22:00:00Z']
                    ]
                ],
                'expectedData' => [
                    'name' => 'Test Price List 01',
                    'active' => true,
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
                    'schedules' => [
                        ['2016-03-01T22:00:00Z', '2016-03-15T22:00:00Z'],
                        ['2016-02-01T22:00:00Z', '2016-02-15T22:00:00Z'],
                    ]
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

    /**
     * @param array $expectedData
     * @param PriceList $result
     */
    protected function assertSchedules(array $expectedData, PriceList $result)
    {
        /** @var PriceListSchedule[] $actualSchedules */
        $actualSchedules = $result->getSchedules()->toArray();
        $expectedSchedules = $expectedData['schedules'];
        foreach ($expectedSchedules as $i => $expected) {
            $actual = $actualSchedules[$i];
            $this->assertSame($result, $actual->getPriceList());
            $this->assertEquals(new \DateTime($expected[0]), $actual->getActiveAt());
            $this->assertEquals(new \DateTime($expected[1]), $actual->getDeactivateAt());
        }
    }
}
