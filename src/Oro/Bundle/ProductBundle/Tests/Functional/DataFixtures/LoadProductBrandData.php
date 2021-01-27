<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductBrandData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private $productsToBrands = [
        LoadBrandData::BRAND_1 => [
            'product-1',
            'product-2',
            'product-8',
        ],
        LoadBrandData::BRAND_2 => [
            'product-3',
            'продукт-9',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductData::class,
            LoadBrandData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->productsToBrands as $brand => $products) {
            /** @var Brand $brand */
            $brand = $this->getReference($brand);
            foreach ($products as $product) {
                /** @var Product $product */
                $product = $this->getReference($product);
                $product->setBrand($brand);
            }
        }

        $manager->flush();

        $this->reindexProductData();
    }

    private function reindexProductData()
    {
        $this->container->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }
}
