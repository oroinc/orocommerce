<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;

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
        $checkout = $this->getDatagridData();
        $this->assertCount(5, $this->getDatagridData());
        $value = 400;
        $checkouts = $this->getDatagridData(['[subtotal][value]' => $value, '[subtotal][type]' => 2]);
        $this->assertCount(3, $checkouts);
        foreach ($checkouts as $checkout) {
            $d = $this->getValue($checkout['subtotal']);
            $this->assertGreaterThan($this->getValue($checkout['subtotal']), $value);
        }
        $checkouts = $this->getDatagridData(['[total][value]' => $value, '[total][type]' => 6]);
        $this->assertCount(4, $checkouts);
        foreach ($checkouts as $checkout) {
            $this->assertLessThan($this->getValue($checkout['total']), $value);
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
        preg_match_all('!\d+(?:\.\d+)?!', $string, $matches);
        $parts = array_map('floatval', $matches[0]);

        return floatval($parts[0] . '.' . $parts[1]);
    }
}
