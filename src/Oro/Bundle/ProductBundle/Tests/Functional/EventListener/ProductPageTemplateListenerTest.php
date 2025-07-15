<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;

final class ProductPageTemplateListenerTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadAttributeFamilyData::class
        ]);
        $this->em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
    }

    public function testPageTemplateFallbackIsSetOnPersist(): void
    {
        $product = new Product();
        $name = new ProductName();
        $name->setString('Test Product');
        $product->setNames([$name]);
        $product->setSku('test-product-page-template');
        $product->setOrganization($this->getReference('organization'));
        $product->setStatus(Product::STATUS_ENABLED);
        $product->setAttributeFamily($this->getReference('attribute_family_1'));

        $this->em->persist($product);
        $this->em->flush();

        $pageTemplate = $product->getPageTemplate();
        self::assertNotNull($pageTemplate, 'Page template fallback should be set');
        self::assertEquals(
            ThemeConfigurationFallbackProvider::FALLBACK_ID,
            $pageTemplate->getFallback(),
            'Page template fallback should be ThemeConfigurationFallbackProvider::FALLBACK_ID'
        );
    }
}
