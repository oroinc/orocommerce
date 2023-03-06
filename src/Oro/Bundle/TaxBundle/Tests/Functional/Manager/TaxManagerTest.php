<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Manager;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Fidry\AliceDataFixtures\LoaderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderWithLineItemsAndTaxes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @dbIsolationPerTest
 */
class TaxManagerTest extends WebTestCase
{
    use ResultComparatorTrait;
    use ConfigManagerAwareTestTrait;

    private ConfigManager $configManager;
    private PropertyAccessorInterface $propertyAccessor;
    private ManagerRegistry $doctrine;
    private LoaderInterface $loader;
    private TaxManager $taxManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->configManager = self::getConfigManager();
        $this->propertyAccessor = $this->getContainer()->get('property_accessor');
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->loader = $this->getContainer()->get('oro_test.alice_fixture_loader');
        $this->taxManager = $this->getContainer()->get('oro_tax.manager.tax_manager');
    }

    protected function tearDown(): void
    {
        $this->clearCache();
        parent::tearDown();
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testMethods(
        string $method,
        string $reference,
        array  $configuration,
        array  $databaseBefore,
        array  $databaseBeforeSecondPart,
        bool   $disableTaxCalculation,
        array  $expectedResult = [],
        array  $databaseAfter = []
    ) {
        $this->loadFixtures([LoadTaxRules::class]);

        foreach ($configuration as $key => $value) {
            $this->configManager->set(sprintf('oro_tax.%s', $key), $value);
        }

        // $databaseBeforeSecondPart is used to avoid problems with relation identifiers during single fixture load
        $this->prepareDatabase($databaseBefore, $databaseBeforeSecondPart, $disableTaxCalculation);
        $this->clearCache();

        $this->executeMethod($method, $this->getReference($reference), $expectedResult);

        $this->assertDatabase($databaseAfter);
    }

    public function methodsDataProvider(): array
    {
        $cases = [];
        $finder = (new Finder())->files()->in(__DIR__ . '/test_cases')->name('*.yml');
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $cases[$file->getRelativePathname()] = Yaml::parse($file->getContents());
        }

        return $cases;
    }

    private function executeMethod(string $method, object $object, array $expectedResult): void
    {
        $manager = $this->getContainer()->get('oro_tax.manager.tax_manager');

        // Refresh the object to get the actual data. Without this move, we may get the order with empty line items.
        self::getContainer()->get('doctrine')->getManagerForClass(get_class($object))->refresh($object);
        $this->compareResult($expectedResult, $manager->{$method}($object, true));

        // cache trigger
        $this->compareResult($expectedResult, $manager->{$method}($object, true));
    }

    private function prepareDatabase(
        array $databaseBefore,
        array $databaseBeforeSecondPart,
        bool  $disableTaxCalculation
    ): void {
        if ($disableTaxCalculation) {
            // Disable taxation for load fixtures
            $previousTaxEnableState = $this->configManager->get('oro_tax.tax_enable');
            $this->configManager->set('oro_tax.tax_enable', false);
        }

        $objectsData = [];
        foreach ([$databaseBefore, $databaseBeforeSecondPart] as $database) {
            $objectsData[] = $this->loadDatabase($database);
        }
        $objectsData = array_merge(...$objectsData);

        if ($disableTaxCalculation) {
            // Restore previous taxation state after load fixtures
            $this->configManager->set('oro_tax.tax_enable', $previousTaxEnableState);
        }
        foreach ($objectsData as $reference => $object) {
            $this->generateMetadataForClass(get_class($object));
            $this->getReferenceRepository()->setReference($reference, $object);
        }
    }

    private function loadDatabase(array $database): array
    {
        foreach ($database as $class => $items) {
            foreach ($items as $reference => $item) {
                $items[$reference] = $this->getReferenceFromDoctrine($item);
            }
            $database[$class] = $items;
        }

        return $this->loader->load($database);
    }

    private function getReferenceFromDoctrine(array $config): array
    {
        foreach ($config as $key => $item) {
            if (is_array($item)) {
                if (array_key_exists('class', $item) && array_key_exists('query', $item)) {
                    $config[$key] = $this->doctrine
                        ->getRepository($item['class'])
                        ->findOneBy($item['query']);
                } elseif (is_numeric(key($item))) {
                    $config[$key] = $this->getReferenceFromDoctrine($item);
                }
            }
        }

        return $config;
    }

    private function assertDatabase(array $databaseAfter): void
    {
        foreach ($databaseAfter as $class => $items) {
            $repository = $this->doctrine->getRepository($class);

            foreach ($items as $item) {
                foreach ($item as $key => $param) {
                    if (is_array($param) && array_key_exists('reference', $param)) {
                        $item[$key] = $this->getReference($param['reference'])->getId();
                    }
                }
                $this->assertNotEmpty(
                    $repository->findBy($item),
                    sprintf('%s %s', $class, json_encode($item, JSON_THROW_ON_ERROR))
                );
            }
        }
    }

    public function testOrderTaxNotRecalculatedIfOrderWasNotChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $initialTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => 0.104,
                'taxableAmount' => '66.6',
                'taxAmount' => 6.92,
                'currency' => 'USD'
            ]
        ];

        $this->assertGetTaxReturnsCorrectTaxes($order, $initialTaxes);

        $this->changeTaxTo('tax.TAX1', 20);

        $this->assertGetTaxReturnsCorrectTaxes($order, $initialTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderItemQuantityChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $this->getReference(LoadOrderItems::ORDER_ITEM_2)->setQuantity(7);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '77.7',
                'taxAmount' => '1554',
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderItemPriceChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_2);
        $price = new Price();
        $price->setCurrency('USD');
        $price->setValue('1234.456');
        $lineItem->setPrice($price);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '14813.48',
                'taxAmount' => '296269.6',
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderItemProductChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_2);
        $lineItem->setProduct($this->getReference(LoadProductData::PRODUCT_2));
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderItemProductUnitChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_2);
        $lineItem->setProductUnit($this->getReference(LoadProductUnits::BOTTLE));
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderItemsCollectionChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderItems::ORDER_ITEM_1);
        $order->removeLineItem($lineItem);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderCustomerChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $order->setCustomer(null);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderBillingAddressPostalCodeChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $address = $order->getBillingAddress();
        $address->setPostalCode('test');
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderBillingAddressStateChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $address = $order->getBillingAddress();
        $address->setRegion(null);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderBillingAddressCountryChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $address = $order->getBillingAddress();
        $address->setCountry(null);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderShippingAddressPostalCodeChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $address = $order->getShippingAddress();
        $address->setPostalCode('test');
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '66.6',
                'taxAmount' => 1332,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderShippingAddressStateChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $address = $order->getShippingAddress();
        $address->setRegion(null);
        $newTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => '20',
                'taxableAmount' => '33.3',
                'taxAmount' => 666,
                'currency' => 'USD'
            ]
        ];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    public function testOrderTaxRecalculatedIfOrderShippingAddressCountryChanged()
    {
        $this->setTaxesConfiguration();

        $this->loadFixtures([LoadOrderWithLineItemsAndTaxes::class]);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->assertSimpleOrderTaxesPrecondition();

        $this->changeTaxTo('tax.TAX1', 20);

        $address = $order->getShippingAddress();
        $address->setCountry(null);
        $newTaxes = [];
        $this->assertGetTaxReturnsCorrectTaxes($order, $newTaxes);
    }

    private function clearCache(): void
    {
        $this->getContainer()->get('oro_tax.taxation_provider.cache')->clear();
        $matchers = self::getContainer()->get('oro_tax.address_matcher_registry')->getMatchers();
        foreach ($matchers as $matcher) {
            if ($matcher instanceof ResetInterface) {
                $matcher->reset();
            }
        }
    }

    private function setTaxesConfiguration(): void
    {
        $this->configManager->set('oro_tax.use_as_base_by_default', 'destination');
        $this->configManager->set('oro_tax.destination', 'shipping_address');
        $this->configManager->set('oro_tax.start_calculation_on', 'item');
        $this->configManager->set('oro_tax.start_calculation_with', 'row_total');
        $this->configManager->set('oro_tax.product_prices_include_tax', false);
    }

    /**
     * Check's test precondition. If they are not met one of next happened:
     * - fixtures was changed
     * - test isolation broken
     */
    private function assertSimpleOrderTaxesPrecondition(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $initialTaxes = [
            [
                'tax' => LoadProductTaxCodes::TAX_1,
                'rate' => 0.104,
                'taxableAmount' => '66.6',
                'taxAmount' => 6.92,
                'currency' => 'USD'
            ]
        ];

        $this->assertGetTaxReturnsCorrectTaxes($order, $initialTaxes);
    }

    private function assertGetTaxReturnsCorrectTaxes(object $entity, array $expectedTaxes): void
    {
        $this->clearCache();
        $taxesCalculationResult = $this->taxManager->getTax($entity);
        $taxes = $taxesCalculationResult->getTaxes();
        $actualTaxes = $this->convertTaxesToComparable($taxes);
        self::assertEquals($expectedTaxes, $actualTaxes);
    }

    private function changeTaxTo(string $code, float $newRate): void
    {
        /** @var Tax $tax */
        $tax = $this->getReference($code);
        $tax->setRate($newRate);
        $this->doctrine->getManager()->flush();
        $this->clearCache();
    }

    private function convertTaxesToComparable(array $taxes): array
    {
        return array_map(function (TaxResultElement $element) {
            return $element->getArrayCopy();
        }, $taxes);
    }

    private function generateMetadataForClass(string $class): void
    {
        $objectManager = $this->doctrine->getManagerForClass($class);

        if (!$objectManager) {
            $objectManager = $this->doctrine->getManager();
        }

        $metadataFactory = $objectManager->getMetadataFactory();

        if ($metadataFactory->hasMetadataFor($class)) {
            return;
        }

        $metadataFactory->setMetadataFor(
            $class,
            new ClassMetadata($class, $objectManager->getConfiguration()->getNamingStrategy())
        );
    }
}
