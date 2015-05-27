<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

class ProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var ProductPriceRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );

        $this->initClient();
        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:ProductPrice');
    }
}
