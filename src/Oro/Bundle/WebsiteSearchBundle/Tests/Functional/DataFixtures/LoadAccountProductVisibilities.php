<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

class LoadAccountProductVisibilities extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    const PRODUCT_ALIAS = 'oro_product_';

    const REFERENCE_PRODUCT_ITEM1 = 'product.item.1';
    const REFERENCE_PRODUCT_ITEM2 = 'product.item.2';

    /**
     * @var array
     */
    private static $productItemData = [
        self::REFERENCE_PRODUCT_ITEM1 => [
            'entity' => TestProduct::class,
            'productReference' => LoadProductsToIndex::REFERENCE_PRODUCT1,
            'title' => 'Good product',
            'websites' => [
                'default' => [
                    'product_visibility_new' => -1,
                    'product_visibility_accounts' => [
                        'account.level_1',
                        'account.level_1.3',
                        'account.orphan'
                    ],
                ],
                LoadOtherWebsite::REFERENCE_OTHER_WEBSITE => [
                    'product_visibility_new' => -1,
                    'product_visibility_accounts' => [
                    ],
                ]
            ],
        ],
        self::REFERENCE_PRODUCT_ITEM2 => [
            'entity' => TestProduct::class,
            'productReference' => LoadProductsToIndex::REFERENCE_PRODUCT2,
            'title' => 'Better product',
            'websites' => [
                'default' => [
                    'product_visibility_new' => -1,
                    'is_visible_by_default' => 1,
                    'product_visibility_accounts' => [
                        'account.level_1',
                        'account.level_1.3',
                        'account.orphan'
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var ReferenceRepository
     */
    private static $searchReferenceRepository;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductVisibilityData::class
        ];
    }

    /**
     * @param string $websiteReference
     * @return Website
     */
    private function getWebsite($websiteReference)
    {
        if ($websiteReference === LoadWebsiteData::DEFAULT_WEBSITE_NAME) {
            $website = $this->container
                ->get('doctrine')
                ->getManagerForClass('OroWebsiteBundle:Website')
                ->getRepository('OroWebsiteBundle:Website')->findOneBy(['name' => $websiteReference]);
        } else {
            $website = $this->getReference($websiteReference);
        }

        return $website;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        self::$searchReferenceRepository = new ReferenceRepository($manager);

        $manager = $this->container->get('oro_entity.doctrine_helper')->getEntityManager(Item::class);

        foreach (self::$productItemData as $itemData) {
            $product = $this->getReference($itemData['productReference']);

            foreach ($itemData['websites'] as $websiteReference => $websiteData) {
                $website = $this->getWebsite($websiteReference);
                $entityAlias = self::PRODUCT_ALIAS . $website->getId();

                $item = new Item;
                $item
                    ->setTitle($itemData['title'])
                    ->setAlias($entityAlias)
                    ->setEntity(Product::class)
                    ->setRecordId($product->getId());

                $isVisibleByDefaultField = new IndexInteger();
                $isVisibleByDefaultField->setField('product_visible_new');
                $isVisibleByDefaultField->setItem($item);
                $isVisibleByDefaultField->setValue($websiteData['product_visible_new']);

                $item->addIntegerField($isVisibleByDefaultField);

                foreach ($websiteData['product_visibility_accounts'] as $accountReference) {
                    /** @var Account $account */
                    $account = $this->getReference($accountReference);

                    $accountVisibility = new IndexInteger();
                    $accountVisibility->setField('visibility_account_' . $account->getId());
                    $accountVisibility->setItem($item);
                    $accountVisibility->setValue(1);

                    $item->addIntegerField($accountVisibility);
                }

                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    /**
     * @param string $referenceName
     * @param int $websiteId
     * @return string
     */
    public static function getReferenceName($referenceName, $websiteId)
    {
        return $referenceName . '_website_' . $websiteId;
    }

    /**
     * @return ReferenceRepository
     * @throw \LogicException
     */
    public static function getSearchReferenceRepository()
    {
        if (null === self::$searchReferenceRepository) {
            throw new \LogicException('The reference repository is not set. Have you loaded fixtures?');
        }

        return self::$searchReferenceRepository;
    }
}
