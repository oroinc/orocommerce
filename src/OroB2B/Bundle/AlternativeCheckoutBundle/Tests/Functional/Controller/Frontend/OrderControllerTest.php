<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    const GRID_NAME = 'frontend-checkouts-grid';
    const TOTAL_VALUE = 400;
    const SUBTOTAL_VALUE = 20;

    /** @var BaseCheckout[] */
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

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
                'OroB2B\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures\LoadAlternativeCheckouts',
                'OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckouts',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices'
            ]
        );
    }

    public function testCheckoutGrid()
    {
        $this->client->request('GET', '/about'); // any page to authorize a user, CMS is used as the fastest one

        $checkouts = $this->getDatagridData();
        $this->assertCount(5, $checkouts);
        $groupedCheckouts = $this->groupCheckoutsByType($checkouts);
        $this->assertCount(2, $groupedCheckouts['alternative']);
        $this->assertCount(3, $groupedCheckouts['base']);
    }

    /**
     * @dataProvider filtersDataProvider
     * @param string $columnName
     * @param float $value
     * @param $filterType
     * @param $expectedCheckouts
     */
    public function testFilters($columnName, $value, $filterType, $expectedCheckouts)
    {
        $checkouts = $this->getDatagridData(
            [
                sprintf('[%s][value]', $columnName) => $value,
                sprintf('[%s][type]', $columnName) => $filterType
            ]
        );

        $this->assertCount(count($expectedCheckouts), $checkouts);

        $expectedGroupedCheckouts = $this->getCheckoutsByReferences($expectedCheckouts);
        $actualGroupedCheckouts = $this->groupCheckoutsByType($checkouts);
        $container = $this->getContainer();
        foreach ($expectedGroupedCheckouts as $checkoutType => $expectedCheckouts) {
            $this->assertTrue(isset($actualGroupedCheckouts[$checkoutType]));
            /** @var  BaseCheckout $expectedCheckout */
            foreach ($expectedCheckouts as $id => $expectedCheckout) {
                $this->assertTrue(isset($actualGroupedCheckouts[$checkoutType][$id]));
                if ($columnName === 'subtotal') {
                    $sourceEntity = $expectedCheckout->getSourceEntity();
                    $propertyAccessor = $container->get('property_accessor');

                    if ($sourceEntity instanceof ShoppingList) {
                        /** @var Subtotal $subtotal */
                        $subtotal = $propertyAccessor->getValue($sourceEntity, $columnName);

                        $formattedPrice = $container->get('oro_locale.twig.number')->formatCurrency(
                            $subtotal->getAmount(),
                            ['currency' => $subtotal->getCurrency()]
                        );
                    } else {
                        $currencyField = property_exists($sourceEntity, 'currency') ? 'currency' : 'totalCurrency';
                        $formattedPrice = $container->get('oro_locale.twig.number')->formatCurrency(
                            $propertyAccessor->getValue($sourceEntity, $columnName),
                            ['currency' => $propertyAccessor->getValue($sourceEntity, $currencyField)]
                        );
                    }

                    $actualCheckout = $actualGroupedCheckouts[$checkoutType][$id];
                    $this->assertEquals($formattedPrice . "\n", $actualCheckout[$columnName]);
                }
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
                'columnName' => 'subtotal',
                'value' => self::SUBTOTAL_VALUE,
                'filterType' => NumberFilterTypeInterface::TYPE_GREATER_THAN,
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
            /** @var BaseCheckout $checkout */
            $checkout = $this->getReference($checkoutReference);
            if ($checkout instanceof Checkout) {
                $result['base'][$checkout->getId()] = $checkout;
            } else {
                $classNamespace = explode('\\', ClassUtils::getClass($checkout));
                $checkoutType = strtolower(str_replace('Checkout', '', end($classNamespace)));
                $result[$checkoutType][$checkout->getId()] = $checkout;
            }
        }

        return $result;
    }

    public function testSorters()
    {
        //check checkouts with subtotal sorter
        $checkouts = $this->getDatagridData(
            [],
            [
                '[subtotal]' => OrmSorterExtension::DIRECTION_ASC,
            ]
        );
        $this->checkSorting($checkouts, 'subtotal', OrmSorterExtension::DIRECTION_ASC);
    }

    /**
     * @param array $checkouts
     * @param string $column
     * @param string $order
     * @param bool $stringSorting
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
     * @param array $filters
     * @param array $sorters
     * @return array
     */
    protected function getDatagridData(array $filters = [], array $sorters = [])
    {
        $result = [];
        foreach ($filters as $filter => $value) {
            $result[self::GRID_NAME . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[self::GRID_NAME . '[_sort_by]' . $sorter] = $value;
        }
        $response = $this->client->requestGrid(['gridName' => self::GRID_NAME], $result);

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @param array $checkouts
     * @return array
     */
    protected function groupCheckoutsByType(array $checkouts)
    {
        $result = [];
        foreach ($checkouts as $checkout) {
            $checkoutType = $checkout['checkoutType'];
            $type = !$checkoutType ? 'base' : $checkoutType;
            $result[$type][$checkout['id']] = $checkout;
        }

        return $result;
    }

    /**
     * @param integer $checkoutId
     * @param string $columnName
     * @return float
     */
    protected function getValue($checkoutId, $columnName)
    {
        $container = $this->getContainer();
        $checkout = $this->getCheckoutById($checkoutId);
        $sourceEntity = $checkout->getSourceEntity();
        $propertyAccessor = $container->get('property_accessor');

        return $propertyAccessor->getValue($sourceEntity, $columnName);
    }

    /**
     * @param int $checkoutId
     * @return BaseCheckout
     */
    protected function getCheckoutById($checkoutId)
    {
        if (empty($this->allCheckouts)) {
            $checkouts = $this->getContainer()->get('doctrine')
                ->getManagerForClass('OroB2BCheckoutBundle:BaseCheckout')
                ->getRepository('OroB2BCheckoutBundle:BaseCheckout')
                ->findAll();

            foreach ($checkouts as $checkout) {
                $this->allCheckouts[$checkout->getId()] = $checkout;
            }
        }

        return $this->allCheckouts[$checkoutId];
    }
}
