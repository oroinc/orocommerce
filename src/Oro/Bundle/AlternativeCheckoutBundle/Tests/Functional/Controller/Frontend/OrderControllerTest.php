<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteAlternativeCheckoutsData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteAlternativeCheckoutsSubtotalsData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;

class OrderControllerTest extends FrontendWebTestCase
{
    const GRID_NAME      = 'frontend-checkouts-grid';
    const TOTAL_VALUE    = 510;
    const SUBTOTAL_VALUE = 300;

    /** @var Checkout[] */
    protected $allCheckouts;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadCombinedProductPrices::class,
                LoadOrders::class,
                LoadQuoteAlternativeCheckoutsData::class,
                LoadQuoteAlternativeCheckoutsSubtotalsData::class,
                LoadShoppingListsCheckoutsData::class,
                LoadShoppingListLineItems::class
            ]
        );
    }

    public function testCheckoutGrid()
    {
        $this->client->request('GET', '/'); // any page to authorize a user

        $checkouts = $this->getDatagridData(self::GRID_NAME);
        $this->assertCount(4, $checkouts);
    }

    /**
     * @dataProvider subtotalFilterDataProvider
     *
     * @param float $value
     * @param string $filterType
     * @param array $expectedCheckouts
     */
    public function testSubtotalFilter($value, $filterType, array $expectedCheckouts)
    {
        $checkouts = $this->getDatagridData(
            self::GRID_NAME,
            [
                sprintf('[%s][value]', 'subtotal') => $value,
                sprintf('[%s][type]', 'subtotal')  => $filterType
            ]
        );

        $this->assertCount(count($expectedCheckouts), $checkouts);

        $expectedCheckouts = $this->getCheckoutsByReferences($expectedCheckouts);
        $actualCheckouts = $this->prepareCheckouts($checkouts);
        $container = $this->getContainer();
        /** @var  Checkout $expectedCheckout */
        foreach ($expectedCheckouts as $id => $expectedCheckout) {
            $this->assertTrue(isset($actualCheckouts[$id]));
            /** @var Subtotal $subtotal */
            $subtotal = $expectedCheckout->getSubtotals()->first()->getSubtotal();

            $formattedPrice = $container->get('oro_locale.formatter.number')->formatCurrency(
                $subtotal->getAmount(),
                $subtotal->getCurrency()
            );

            $actualCheckout = $actualCheckouts[$id];
            $this->assertEquals($formattedPrice . "\n", $actualCheckout['subtotal']);
        }
    }

    /**
     * @return array
     */
    public function subtotalFilterDataProvider()
    {
        return [
            'greater than' => [
                'value' => self::SUBTOTAL_VALUE,
                'filterType' => NumberFilterTypeInterface::TYPE_GREATER_THAN,
                'expectedCheckouts' => ['checkout.1', 'alternative.checkout.2']
            ]
        ];
    }

    /**
     * @dataProvider totalFilterDataProvider
     *
     * @param float $value
     * @param string $filterType
     * @param array $expectedCheckouts
     */
    public function testTotalFilter($value, $filterType, array $expectedCheckouts)
    {
        $checkouts = $this->getDatagridData(
            self::GRID_NAME,
            [
                sprintf('[%s][value]', 'total') => $value,
                sprintf('[%s][type]', 'total')  => $filterType
            ]
        );

        $this->assertCount(count($expectedCheckouts), $checkouts);

        $expectedCheckoutIds = array_keys($this->getCheckoutsByReferences($expectedCheckouts));
        $actualCheckouts = $this->prepareCheckouts($checkouts);
        /** @var  Checkout $expectedCheckout */
        foreach ($expectedCheckoutIds as $id) {
            $this->assertTrue(isset($actualCheckouts[$id]));
            $actualCheckout = $actualCheckouts[$id];
            $this->assertEquals(
                (float) $actualCheckout['subtotal'] + (float) $actualCheckout['shippingCost'],
                (float) $actualCheckout['total']
            );
        }
    }

    /**
     * @return array
     */
    public function totalFilterDataProvider()
    {
        return [
            'equal' => [
                'value' => self::TOTAL_VALUE,
                'filterType' => NumberFilterTypeInterface::TYPE_EQUAL,
                'expectedCheckouts' => ['checkout.1']
            ]
        ];
    }

    /**
     * @param array|Checkout[] $checkoutReferences
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
        $lastValue = null;
        foreach ($checkouts as $checkout) {
            /** @var Subtotal|string $actualValue */
            $actualValue = $stringSorting ? $checkout[$column] : $this->getSubtotalValue($checkout['id']);
            $actualValue = ($actualValue instanceof Subtotal) ? $actualValue->getAmount() : $actualValue;

            if (null !== $lastValue) {
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

        $response = $this->client->requestFrontendGrid(['gridName' => $gridName], $result);

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
     * @return float
     */
    protected function getSubtotalValue($checkoutId)
    {
        $checkout = $this->getCheckoutById($checkoutId);
        if (0 === $checkout->getSubtotals()->count()) {
            return 0;
        }

        return $checkout->getSubtotals()->first()->getSubtotal()->getAmount();
    }

    /**
     * @param int $checkoutId
     * @return Checkout
     */
    protected function getCheckoutById($checkoutId)
    {
        if (empty($this->allCheckouts)) {
            /** @var array|Checkout[] $checkouts */
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
