<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class PriceListTypeTest extends FormIntegrationTestCase
{
    use PriceRuleEditorAwareTestTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);
        $currencyProvider->expects($this->any())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        return [
            new PreloadedExtension(
                array_merge([
                    new CurrencySelectionType(
                        $currencyProvider,
                        $this->createMock(LocaleSettings::class),
                        $this->createMock(CurrencyNameHelper::class)
                    ),
                    EntityType::class => new EntityTypeStub(['item' => (new ProductUnit())->setCode('item')])
                ], $this->getPriceRuleEditorExtension()),
                []
            )
        ];
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    private function getCustomer(int $id): Customer
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, $id);

        return $customer;
    }

    private function getCustomerGroup(int $id): CustomerGroup
    {
        $customerGroup = new CustomerGroup();
        ReflectionUtil::setId($customerGroup, $id);

        return $customerGroup;
    }

    private function assertSchedules(array $expectedData, PriceList $result): void
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

    public function testBuildForm()
    {
        $form = $this->factory->create(PriceListType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('currencies'));
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(mixed $defaultData, mixed $submittedData, mixed $expectedData)
    {
        if ($defaultData) {
            $existingPriceList = new PriceList();
            ReflectionUtil::setId($existingPriceList, 42);
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

    public function submitDataProvider(): array
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
                    'appendCustomers' => [$this->getCustomer(1)],
                    'removeCustomers' => [$this->getCustomer(2)],
                    'appendCustomerGroups' => [$this->getCustomerGroup(3)],
                    'removeCustomerGroups' => [$this->getCustomerGroup(4)],
                    'appendWebsites' => [$this->getWebsite(5)],
                    'removeWebsites' => [$this->getWebsite(6)],
                    'schedules' => [
                        ['2016-03-01T22:00:00Z', '2016-03-15T22:00:00Z'],
                        ['2016-02-01T22:00:00Z', '2016-02-15T22:00:00Z'],
                    ]
                ]
            ]
        ];
    }
}
