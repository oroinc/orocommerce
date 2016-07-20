<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;

/**
 * @dbIsolation
 */
class CategoryEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCategoryData::class,
            LoadPriceLists::class,
        ]);
        $this->removeTriggers();
    }

    public function testOnDelete()
    {
        $removedCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL2);
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_3);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->createLexemes([
            // ancestor category - trigger expected
            [
                'category' => $em->getRepository(Category::class)->getMasterCatalogRoot(),
                'priceList' => $priceList1,
            ],
            // removed category - trigger expected
            [
                'category' => $removedCategory,
                'priceList' => $priceList2,
            ],
            // child category - trigger expected
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL2),
                'priceList' => $priceList3,
            ],
            // ensure that similar triggers won't be created
            [
                'category' => $this->getReference(LoadCategoryData::FIRST_LEVEL),
                'priceList' => $priceList1,
            ],
            // not affected category
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL1),
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_4),
            ]
        ]);

        $em->remove($removedCategory);
        $em->flush();

        $actual = $this->getActualTriggersPriceLists();
        $this->assertCount(3, $actual);
        $this->assertContains($priceList1, $actual);
        $this->assertContains($priceList2, $actual);
        $this->assertContains($priceList3, $actual);
    }

    public function testOnUpdate()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL2);
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->createLexemes([
            // removed category - trigger expected
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL2),
                'priceList' => $priceList1,
            ],
            // child category - trigger expected
            [
                'category' => $this->getReference(LoadCategoryData::SECOND_LEVEL2),
                'priceList' => $priceList2,
            ]
        ]);

        $category->setCreatedAt(new \DateTime());
        $em->flush();

        $actual = $this->getActualTriggersPriceLists();
        $this->assertCount(2, $actual);
        $this->assertContains($priceList1, $actual);
        $this->assertContains($priceList2, $actual);
    }

    /**
     * @param array $data
     */
    protected function createLexemes(array $data)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        foreach ($data as $item) {
            $lexeme = new PriceRuleLexeme();
            /** @var Category $category */
            $category = $item['category'];
            $lexeme->setClassName(Category::class)
                ->setFieldName('id')
                ->setPriceList($item['priceList'])
                ->setRelationId($category->getId());
            $em->persist($lexeme);
        }

        $em->flush();
    }

    /**
     * @return PriceList[]
     */
    protected function getActualTriggersPriceLists()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository(PriceRuleChangeTrigger::class);
        return array_map(
            function (PriceRuleChangeTrigger $trigger) {
                return $trigger->getPriceList();
            },
            $repository->findBy([])
        );
    }

    /**
     * @return PriceList[]
     */
    protected function removeTriggers()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository(PriceRuleChangeTrigger::class);
        $repository->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
