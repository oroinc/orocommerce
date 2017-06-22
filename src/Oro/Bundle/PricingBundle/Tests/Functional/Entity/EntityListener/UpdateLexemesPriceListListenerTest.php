<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateLexemesPriceListListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceLists::class,
        ]);
    }

    public function testPostPersist()
    {
        $priceList = new PriceList();
        $priceList->setName('Test')
            ->setCurrencies(['EUR', 'USD'])
            ->setProductAssignmentRule('product.category.id == 2');

        $entityManager = $this->getEntityManager();
        $entityManager->persist($priceList);
        $entityManager->flush();

        $this->assertLexemesCreated($priceList, 2);
    }

    public function testPostUpdate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $priceList->setProductAssignmentRule('product.category.id == 2');

        $this->getEntityManager()->flush();

        $this->assertLexemesCreated($priceList, 2);
    }

    /**
     * @param PriceList $priceList
     * @param int       $count
     */
    private function assertLexemesCreated(PriceList $priceList, int $count)
    {
        $lexemes = $this->getEntityManager()
            ->getRepository('OroPricingBundle:PriceRuleLexeme')
            ->findBy(['priceList' => $priceList]);

        static::assertCount($count, $lexemes);
    }

    /**
     * @return ObjectManager
     */
    private function getEntityManager(): ObjectManager
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
