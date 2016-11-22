<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures\LoadAlternativeCheckouts;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;

/**
 * @dbIsolation
 */
class OrderControllerTest extends FrontendWebTestCase
{
    const GRID_NAME      = 'frontend-checkouts-grid';
    const TOTAL_VALUE    = 400;
    const SUBTOTAL_VALUE = 20;

    /** @var Checkout[] */
    protected $allCheckouts;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadOrders::class,
                LoadAlternativeCheckouts::class,
                LoadShoppingListsCheckoutsData::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class
            ]
        );
    }

    public function testCheckoutGrid()
    {
        $this->client->request('GET', '/'); // any page to authorize a user

        $checkouts = $this->getDatagridData(self::GRID_NAME);
        $this->assertCount(5, $checkouts);
    }

    /**
     * @dataProvider filtersDataProvider
     * @param string $columnName
     * @param float  $value
     * @param        $filterType
     * @param        $expectedCheckouts
     */
    public function testFilters($columnName, $value, $filterType, $expectedCheckouts)
    {
        $checkouts = $this->getDatagridData(
            self::GRID_NAME,
            [
                sprintf('[%s][value]', $columnName) => $value,
                sprintf('[%s][type]', $columnName)  => $filterType
            ]
        );

        $this->assertCount(count($expectedCheckouts), $checkouts);

        $expectedCheckouts = $this->getCheckoutsByReferences($expectedCheckouts);
        $actualCheckouts   = $this->prepareCheckouts($checkouts);
        $container         = $this->getContainer();
        /** @var  Checkout $expectedCheckout */
        foreach ($expectedCheckouts as $id => $expectedCheckout) {
            $this->assertTrue(isset($actualCheckouts[$id]));
            if ($columnName === 'subtotal') {
                $sourceEntity     = $expectedCheckout->getSourceEntity();
                $propertyAccessor = $container->get('property_accessor');

                if ($sourceEntity instanceof ShoppingList) {
                    /** @var Subtotal $subtotal */
                    $subtotal = $propertyAccessor->getValue($sourceEntity, $columnName);

                    $formattedPrice = $container->get('oro_locale.twig.number')->formatCurrency(
                        $subtotal->getAmount(),
                        ['currency' => $subtotal->getCurrency()]
                    );
                } else {
                    $currencyField  = property_exists($sourceEntity, 'currency') ? 'currency' : 'totalCurrency';
                    $formattedPrice = $container->get('oro_locale.twig.number')->formatCurrency(
                        $propertyAccessor->getValue($sourceEntity, $columnName),
                        ['currency' => $propertyAccessor->getValue($sourceEntity, $currencyField)]
                    );
                }

                $actualCheckout = $actualCheckouts[$id];
                $this->assertEquals($formattedPrice . "\n", $actualCheckout[$columnName]);
            }
        }
    }

    /**
     * @return array
     */
    public function filtersDataProvider()
    {
        return [
            'subtotal' => [
                'columnName'        => 'subtotal',
                'value'             => self::SUBTOTAL_VALUE,
                'filterType'        => NumberFilterTypeInterface::TYPE_GREATER_THAN,
                'expectedCheckouts' => ['checkout.1', 'alternative.checkout.1', 'alternative.checkout.2']
            ]
        ];
    }

    /**
     * @param array $checkoutReferences
     * @return array
     */
    protected function getCheckoutsByReferences(array $checkoutReferences)
    {
        $result = [];
        foreach ($checkoutReferences as $checkoutReference) {
            /** @var Checkout $checkout */
            $checkout                   = $this->getReference($checkoutReference);
            $result[$checkout->getId()] = $checkout;
        }

        return $result;
    }

    public function testSorters()
    {
        //check checkouts with subtotal sorter
        $checkouts = $this->getDatagridData(
            self::GRID_NAME,
            [],
            [
                '[subtotal]' => OrmSorterExtension::DIRECTION_ASC,
            ]
        );
        $this->checkSorting($checkouts, 'subtotal', OrmSorterExtension::DIRECTION_ASC);
    }

    /**
     * @param array  $checkouts
     * @param string $column
     * @param string $order
     * @param bool   $stringSorting
     */
    protected function checkSorting(array $checkouts, $column, $order, $stringSorting = false)
    {
        foreach ($checkouts as $checkout) {
            /** @var Subtotal|string $actualValue */
            $actualValue = $stringSorting ? $checkout[$column] : $this->getValue($checkout['id'], $column);
            $actualValue = ($actualValue instanceof Subtotal) ? $actualValue->getAmount() : $actualValue;

            if (isset($lastValue)) {
                if ($order === OrmSorterExtension::DIRECTION_DESC) {
                    $this->assertGreaterThanOrEqual($actualValue, $lastValue);
                } elseif ($order === OrmSorterExtension::DIRECTION_ASC) {
                    $this->assertLessThanOrEqual($actualValue, $lastValue);
                }
            }
            $lastValue = $actualValue;
        }
    }

    /**
     * @param string $gridName
     * @param array  $filters
     * @param array  $sorters
     * @return array
     */
    protected function getDatagridData($gridName, array $filters = [], array $sorters = [])
    {
        $result = [];
        foreach ($filters as $filter => $value) {
            $result[$gridName . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[$gridName . '[_sort_by]' . $sorter] = $value;
        }
        $response = $this->client->requestGrid(['gridName' => $gridName], $result);

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @param array $checkouts
     * @return array
     */
    protected function prepareCheckouts(array $checkouts)
    {
        $result = [];
        foreach ($checkouts as $checkout) {
            $result[$checkout['id']] = $checkout;
        }

        return $result;
    }

    /**
     * @param integer $checkoutId
     * @param string  $columnName
     * @return float
     */
    protected function getValue($checkoutId, $columnName)
    {
        $container        = $this->getContainer();
        $checkout         = $this->getCheckoutById($checkoutId);
        $sourceEntity     = $checkout->getSourceEntity();
        $propertyAccessor = $container->get('property_accessor');

        return $propertyAccessor->getValue($sourceEntity, $columnName);
    }

    /**
     * @param int $checkoutId
     * @return Checkout
     */
    protected function getCheckoutById($checkoutId)
    {
        if (empty($this->allCheckouts)) {
            $checkouts = $this->getContainer()->get('doctrine')
                ->getManagerForClass('OroCheckoutBundle:Checkout')
                ->getRepository('OroCheckoutBundle:Checkout')
                ->findAll();

            foreach ($checkouts as $checkout) {
                $this->allCheckouts[$checkout->getId()] = $checkout;
            }
        }

        return $this->allCheckouts[$checkoutId];
    }
}
