<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadDependentPriceLists extends AbstractFixture implements DependentFixtureInterface
{
    const DEPENDENT_PRICE_LIST_1 = 'dependent_price_list_1';
    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => 'dependentPriceList1',
            'parentPriceList' => LoadPriceLists::PRICE_LIST_1,
            'reference' => self::DEPENDENT_PRICE_LIST_1,
            'default' => false,
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
            'active' => true,
            'assignmentRule' => 'product.id in pricelist[%d].assignedProducts',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach (self::$data as $priceListData) {
            $priceList = new PriceList();

            $parentPriceListId = $this->getReference($priceListData['parentPriceList'])->getId();

            $priceList
                ->setName($priceListData['name'])
                ->setCurrencies($priceListData['currencies'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
                ->setActive($priceListData['active'])
                ->setOrganization($this->getReference('organization'))
                ->setProductAssignmentRule(
                    sprintf($priceListData['assignmentRule'], $parentPriceListId)
                );

            $manager->persist($priceList);
            $this->setReference($priceListData['reference'], $priceList);

            $lexemeEntity = new PriceRuleLexeme();
            $lexemeEntity
                ->setClassName(PriceList::class)
                ->setFieldName('assignedProducts')
                ->setPriceList($priceList)
                ->setRelationId($parentPriceListId);
            $manager->persist($lexemeEntity);
        }

        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getDependencies()
    {
        return [LoadOrganization::class, LoadPriceLists::class];
    }
}
