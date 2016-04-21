<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    const GRID_NAME = 'frontend-checkouts-grid';

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
                'OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckouts'
            ]
        );
    }

    public function testCheckoutGrid()
    {
        $value = 400.123;

        //check checkouts without filter
        $this->assertCount(5, $this->getDatagridData());

        //check checkouts with subtotal filter
        $checkouts = $this->getDatagridData(['[subtotal][value]' => $value, '[subtotal][type]' => 2]);
        $this->assertCount(3, $checkouts);
        foreach ($checkouts as $checkout) {
            $this->assertGreaterThan($value, $this->getValue($checkout['subtotal']));
        }

        //check checkouts with total filter
        $checkouts = $this->getDatagridData(['[total][value]' => $value, '[total][type]' => 6]);
        $this->assertCount(4, $checkouts);
        foreach ($checkouts as $checkout) {
            $this->assertLessThan($value, $this->getValue($checkout['total']));
        }

        //check checkouts with Pay flow Gateway filter
        $checkouts = $this->getDatagridData(
            ['[paymentMethod][value]' => PayflowGateway::TYPE, '[paymentMethod][type]' => 1]
        );
        $this->assertCount(2, $checkouts);
        foreach ($checkouts as $checkout) {
            $this->assertContains('Credit Card', $checkout['paymentMethod']);
        }

        //check checkouts with Payment Term filter
        $checkouts = $this->getDatagridData(
            ['[paymentMethod][value]' => PaymentTerm::TYPE, '[paymentMethod][type]' => 1]
        );
        $this->assertCount(2, $checkouts);
        foreach ($checkouts as $checkout) {
            $this->assertContains('Payment Terms', $checkout['paymentMethod']);
        }
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getDatagridData(array $filters = [])
    {
        $resultFilters = [];
        foreach ($filters as $filter => $value) {
            $resultFilters[self::GRID_NAME . '[_filter]' . $filter] = $value;
        }
        $response = $this->requestFrontendGrid(['gridName' => self::GRID_NAME], $resultFilters);

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @param $string
     * @return float
     */
    protected function getValue($string)
    {
        $string = str_replace(',', '', $string);
        preg_match_all('~\d+(?:\.\d+)?~', $string, $matches);

        return array_map('floatval', $matches[0])[0];
    }
}
