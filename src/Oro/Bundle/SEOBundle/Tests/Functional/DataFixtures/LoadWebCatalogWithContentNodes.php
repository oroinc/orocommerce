<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Migrations\Schema\OroSEOBundleInstaller;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadWebCatalogWithContentNodes extends AbstractFixture implements DependentFixtureInterface
{
    use SEOMetaDataFieldsTrait;
    
    const WEB_CATALOG_NAME = 'web catalog name';

    const META_DESCRTIPTION = 'web_catalog_meta_description';
    const META_KEYWORDS = 'web_catalog_meta_keywords';
    
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadFrontendProductData::class,
            LoadProductMetaData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $webCatalog = new WebCatalog();
        $webCatalog->setName(self::WEB_CATALOG_NAME);
        
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        
        $productContentVariant = new ContentVariant();
        $productContentVariant->setType(ProductPageContentVariantType::TYPE);
        $productContentVariant->setProductPageProduct($product);
        $productContentVariant->setDefault(true);
        
        $contentNode = new ContentNode();
        $contentNode->setWebCatalog($webCatalog);
        $contentNode->addContentVariant($productContentVariant);
        $contentNode->setWebCatalog($webCatalog);
        
        $this->loadLocalizedFallbackValues($manager, $contentNode, [
            OroSEOBundleInstaller::METAINFORMATION_DESCRIPTIONS => self::META_DESCRTIPTION,
            OroSEOBundleInstaller::METAINFORMATION_KEYWORDS => self::META_KEYWORDS,
        ]);
        
        $manager->persist($product);
        $manager->persist($productContentVariant);
        $manager->persist($webCatalog);
        $manager->persist($contentNode);
        $manager->flush();
    }
}
