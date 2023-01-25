<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddressACLData;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class QuickAddCollectionPriceProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCombinedProductPrices::class]);

        $request = Request::createFromGlobals();
        $this->loginUser(LoadCustomerUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $request->query->set(
            ProductPriceScopeCriteriaRequestHandler::CUSTOMER_ID_KEY,
            $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1)->getId()
        );

        self::getContainer()->get('request_stack')->push($request);

        $this->productRepository = self::getContainer()->get('oro_product.repository.product');
    }

    protected function tearDown(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );
    }

    public function testIfCorrectPricesAreBeingAddedToRowItems(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );

        $collection = $this->getValidCollection();
        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch(
            $event,
            QuickAddRowsCollectionReadyEvent::NAME
        );

        $expectedResults = [
            'price' => [
                new QuickAddField('price', ['value' => 13.1, 'currency' => 'USD']),
                new QuickAddField('price', ['value' => 20, 'currency' => 'USD']),
                null
            ],
            'unitPrice' => [
                new QuickAddField('unitPrice', ['value' => 13.1, 'currency' => 'USD']),
                new QuickAddField('unitPrice', ['value' => 20, 'currency' => 'USD']),
                null
            ],
        ];
        foreach ($collection as $i => $quickAddRow) {
            self::assertEquals($expectedResults['price'][$i], $quickAddRow->getAdditionalField('price'));
            self::assertEquals($expectedResults['unitPrice'][$i], $quickAddRow->getAdditionalField('unitPrice'));
        }
    }

    public function testIfCorrectPricesAreBeingAddedToRowItemsWhenIsOptimized(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            true
        );

        $collection = $this->getValidCollection();
        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch($event, QuickAddRowsCollectionReadyEvent::NAME);

        $expectedResults = [
            new QuickAddField('prices', [
                'bottle' => [['price' => 13.1, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'bottle']],
                'liter' => [
                    ['price' => 10.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'liter'],
                    ['price' => 12.2, 'currency' => 'USD', 'quantity' => 10.0, 'unit' => 'liter'],
                ],
                'milliliter' => [['price' => 0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'milliliter']],
            ]),
            new QuickAddField('prices', [
                'liter' => [
                    ['price' => 20.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'liter'],
                    ['price' => 12.2, 'currency' => 'USD', 'quantity' => 12.0, 'unit' => 'liter'],
                ],
                'milliliter' => [['price' => 0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'milliliter']],
            ]),
            new QuickAddField('prices', [
                'bottle' => [['price' => 200.5, 'currency' => 'USD', 'quantity' => 10.0, 'unit' => 'bottle']],
                'milliliter' => [['price' => 0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'milliliter']],
            ]),
        ];

        foreach ($collection as $i => $quickAddRow) {
            self::assertEquals($expectedResults[$i], $quickAddRow->getAdditionalField('prices'));
        }
    }

    public function testIfCollectionSubtotalIsBeingCalculated(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );

        $collection = $this->getValidCollection();
        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch(
            $event,
            QuickAddRowsCollectionReadyEvent::NAME
        );

        self::assertEquals('33.1', $collection->getAdditionalField('price')->getValue()['value']);
        self::assertEquals('USD', $collection->getAdditionalField('price')->getValue()['currency']);
    }

    public function testIfPriceIsCalculatedForFloatQuantityValues(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );

        $collection = $this->getValidCollection();

        $quickAddRow4 = new QuickAddRow(4, 'product-1', 12.5, 'liter');
        $quickAddRow4->setProduct($this->productRepository->findOneBySku('product-1'));
        $quickAddRow4->setValid(1);

        $collection->add($quickAddRow4);

        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch(
            $event,
            QuickAddRowsCollectionReadyEvent::NAME
        );

        self::assertEquals('185.6', $collection->getAdditionalField('price')->getValue()['value']);
        self::assertEquals('USD', $collection->getAdditionalField('price')->getValue()['currency']);
    }

    public function testIfOnlyValidRowsAreBeingCalculated(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );

        $collection = $this->getValidCollection();
        $collection->get(1)->setValid(0);
        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch(
            $event,
            QuickAddRowsCollectionReadyEvent::NAME
        );

        $expectedResults = [
            'price' => [
                new QuickAddField('price', ['value' => 13.1, 'currency' => 'USD']),
                null,
                null
            ],
            'unitPrice' => [
                new QuickAddField('unitPrice', ['value' => 13.1, 'currency' => 'USD']),
                null,
                null
            ],
        ];
        foreach ($collection as $i => $quickAddRow) {
            self::assertEquals($expectedResults['price'][$i], $quickAddRow->getAdditionalField('price'));
            self::assertEquals($expectedResults['unitPrice'][$i], $quickAddRow->getAdditionalField('unitPrice'));
        }

        self::assertEquals('13.1', $collection->getAdditionalField('price')->getValue()['value']);
        self::assertEquals('USD', $collection->getAdditionalField('price')->getValue()['currency']);
    }

    public function testIfSubtotalIsNullIfCollectionHasNoValidRows(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );

        $collection = new QuickAddRowCollection();
        $lineNumber = 0;

        $quickAddRow1 = new QuickAddRow(++$lineNumber, 'product-1', 1, 'bottle');
        $quickAddRow1->setProduct($this->productRepository->findOneBySku('product-1'));

        $quickAddRow2 = new QuickAddRow(++$lineNumber, 'product-2', 1, 'liter');
        $quickAddRow2->setProduct($this->productRepository->findOneBySku('product-2'));

        $quickAddRow3 = new QuickAddRow(++$lineNumber, 'product-4', 1, 'bottle');
        $quickAddRow3->setProduct($this->productRepository->findOneBySku('product-4'));

        $collection->add($quickAddRow1);
        $collection->add($quickAddRow2);
        $collection->add($quickAddRow3);

        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch(
            $event,
            QuickAddRowsCollectionReadyEvent::NAME
        );

        self::assertNull($collection->getAdditionalField('price')->getValue()['value']);
        self::assertEquals('USD', $collection->getAdditionalField('price')->getValue()['currency']);
    }

    public function testIfPriceIsNullIfCollectionHasNoRows(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            false
        );

        $collection = new QuickAddRowCollection();

        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch(
            $event,
            QuickAddRowsCollectionReadyEvent::NAME
        );

        self::assertNull($collection->getAdditionalField('price'));
    }

    public function testIfPriceIsNullIfCollectionHasNoRowsWhenIsOptimized(): void
    {
        self::getConfigManager()->set(
            Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED),
            true
        );

        $collection = new QuickAddRowCollection();

        $event = new QuickAddRowsCollectionReadyEvent($collection);

        self::getContainer()->get('event_dispatcher')->dispatch($event, QuickAddRowsCollectionReadyEvent::NAME);

        self::assertEquals(null, $collection->getAdditionalField('prices'));
    }

    private function getValidCollection(): QuickAddRowCollection
    {
        $collection = new QuickAddRowCollection();
        $lineNumber = 0;

        $quickAddRow1 = new QuickAddRow(++$lineNumber, 'product-1', 1, 'bottle');
        $quickAddRow1->setProduct($this->productRepository->findOneBySku('product-1'));
        $quickAddRow1->setValid(1);

        $quickAddRow2 = new QuickAddRow(++$lineNumber, 'product-2', 1, 'liter');
        $quickAddRow2->setProduct($this->productRepository->findOneBySku('product-2'));
        $quickAddRow2->setValid(1);

        $quickAddRow3 = new QuickAddRow(++$lineNumber, 'product-4', 1, 'bottle');
        $quickAddRow3->setProduct($this->productRepository->findOneBySku('product-4'));
        $quickAddRow3->setValid(1);

        $collection->add($quickAddRow1);
        $collection->add($quickAddRow2);
        $collection->add($quickAddRow3);

        return $collection;
    }
}
