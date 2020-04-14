<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Provider;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\SaleBundle\Provider\QuoteCheckoutProvider;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

class QuoteCheckoutProviderTest extends FrontendWebTestCase
{
    /**
     * @var QuoteCheckoutProvider
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadQuoteCheckoutsData::class,
                LoadCustomerUserData::class,
            ]
        );

        $this->provider = $this->getContainer()->get('oro_sale.provider.quote_checkout');
    }

    public function testGetCheckoutByQuote()
    {
        $quote = $this->getReference(LoadQuoteData::QUOTE1);
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        $this->assertSame(
            $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1),
            $this->provider->getCheckoutByQuote($quote, $customerUser, 'b2b_flow_checkout')
        );
    }
}
