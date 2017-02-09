<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData as BaseLoadQuoteCheckoutsData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CheckoutRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadQuoteCheckoutsData::class,
                BaseLoadQuoteCheckoutsData::class,
                LoadCustomerUserData::class,
            ]
        );
    }

    /**
     * @return CheckoutRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(Checkout::class);
    }

    /**
     * @param string $checkout
     * @param string $workflowName
     * @dataProvider findCheckoutByCustomerUserAndSourceCriteriaByQuoteDemandProvider
     */
    public function testFindCheckoutByCustomerUserAndSourceCriteriaByQuoteDemand($checkout, $workflowName)
    {
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $criteria = ['quoteDemand' => $this->getReference(LoadQuoteProductDemandData::QUOTE_DEMAND_1)];

        $this->assertSame(
            $this->getReference($checkout),
            $this->getRepository()->findCheckoutByCustomerUserAndSourceCriteria($customerUser, $criteria, $workflowName)
        );
    }

    /**
     * @return array
     */
    public function findCheckoutByCustomerUserAndSourceCriteriaByQuoteDemandProvider()
    {
        return [
            'checkout' => [
                BaseLoadQuoteCheckoutsData::CHECKOUT_1,
                'b2b_flow_checkout',
            ],
            'alternative checkout' => [
                LoadQuoteCheckoutsData::CHECKOUT_1,
                'b2b_flow_alternative_checkout',
            ],
        ];
    }
}
