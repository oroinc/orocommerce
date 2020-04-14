<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Operation;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Symfony\Component\HttpFoundation\Response;

class PriceListDisableOperationTest extends ActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([LoadPriceLists::class]);
        parent::setUp();
    }

    public function testDisableSuccess()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        // assert that action button exists
        $crawler = $this->client
            ->request('GET', $this->getUrl('oro_pricing_price_list_view', ['id' => $priceList->getId()]));
        $this->assertCount(1, $crawler->filter('.action-button:contains(Disable)'));

        // check that action call is successful
        $this->assertExecuteOperation(
            'oro_pricing_price_list_disable',
            $priceList->getId(),
            PriceList::class,
            [],
            [],
            Response::HTTP_OK
        );

        // assert that price list is disabled
        $priceListAfterUpdate = $this->getRepository()->findOneBy(['name' => $priceList->getName()]);
        $this->assertFalse($priceListAfterUpdate->isActive());
    }

    public function testDisableFailedForDisabledPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_6);

        // assert that action button not exists
        $crawler = $this->client
            ->request('GET', $this->getUrl('oro_pricing_price_list_view', ['id' => $priceList->getId()]));
        $this->assertCount(0, $crawler->filter('.action-button:contains(Disable)'));

        // check that action call is denied
        $this->assertExecuteOperation(
            'oro_pricing_price_list_disable',
            $priceList->getId(),
            PriceList::class,
            [],
            [],
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceList::class);
    }
}
