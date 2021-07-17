<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class PriceListTypeTest extends FormIntegrationTestCase
{
    use PriceRuleEditorAwareTestTrait;

    const ACCOUNT_CLASS = 'Oro\Bundle\CustomerBundle\Entity\Customer';
    const ACCOUNT_GROUP_CLASS = 'Oro\Bundle\CustomerBundle\Entity\CustomerGroup';
    const WEBSITE_CLASS = 'Oro\Bundle\WebsiteBundle\Entity\Website';

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|CurrencyProviderInterface $currencyProvider */
        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->method('getCurrencyList')->willReturn(['USD', 'EUR']);
        $currencyProvider->method('getDefaultCurrency')->willReturn('USD');

        /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings $localeSettings */
        $localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();

        /** @var CurrencyNameHelper|\PHPUnit\Framework\MockObject\MockObject $currencyNameHelper */
        $currencyNameHelper = $this->getMockBuilder(CurrencyNameHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityIdentifierType = new EntityTypeStub(
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
                array_merge(
                    [
                        $entityIdentifierType->getName() => $entityIdentifierType,
                        CurrencySelectionType::class => new CurrencySelectionType(
                            $currencyProvider,
                            $localeSettings,
                            $currencyNameHelper
                        ),
                        EntityType::class => new EntityTypeStub(['item' => (new ProductUnit())->setCode('item')])
                    ],
                    $this->getPriceRuleEditorExtension()
                ),
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(PriceListType::class);

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

        $form = $this->factory->create(PriceListType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        if (isset($existingPriceList)) {
            $this->assertEquals($existingPriceList, $form->getViewData());
        } else {
            $this->assertNull($form->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

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
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
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
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
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
                    'appendCustomers' => [],
                    'removeCustomers' => [],
                    'appendCustomerGroups' => [],
                    'removeCustomerGroups' => [],
                    'appendWebsites' => [],
                    'removeWebsites' => [],
                ],
                'submittedData' => [
                    'name' => 'Test Price List 01',
                    'active' => false,
                    'currencies' => ['EUR', 'USD'],
                    'appendCustomers' => [1],
                    'removeCustomers' => [2],
                    'appendCustomerGroups' => [3],
                    'removeCustomerGroups' => [4],
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
                    'appendCustomers' => [$this->getEntity(self::ACCOUNT_CLASS, 1)],
                    'removeCustomers' => [$this->getEntity(self::ACCOUNT_CLASS, 2)],
                    'appendCustomerGroups' => [
                        $this->getEntity(self::ACCOUNT_GROUP_CLASS, 3)
                    ],
                    'removeCustomerGroups' => [
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
