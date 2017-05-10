<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;

class LoadWebCatalogProductLimitationData extends AbstractFixture implements DependentFixtureInterface
{
    const VERSION = 1;
    const ALT_VERSION = 2;

    /**
     * @var array
     */
    public static $webCatalogProducts = [
        LoadProductData::PRODUCT_1 => self::VERSION,
        LoadProductData::PRODUCT_3 => self::VERSION,
        LoadProductData::PRODUCT_5 => self::VERSION,
        LoadProductData::PRODUCT_7 => self::VERSION,
        LoadProductData::PRODUCT_8 => self::ALT_VERSION,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$webCatalogProducts as $product => $version) {
            $product = $this->getReference($product);
            $webCatalogProductLimitation = new WebCatalogProductLimitation();
            $webCatalogProductLimitation->setProductId($product->getId());
            $webCatalogProductLimitation->setVersion($version);

            $manager->persist($webCatalogProductLimitation);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductData::class];
    }
}
