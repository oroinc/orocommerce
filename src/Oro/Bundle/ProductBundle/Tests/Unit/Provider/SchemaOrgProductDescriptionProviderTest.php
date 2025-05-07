<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionProvider;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionProviderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SchemaOrgProductDescriptionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIGURATION_OPTION = 'test_option';
    private const PRODUCT_DESCRIPTION = 'test_description';

    private SchemaOrgProductDescriptionProvider $productDescriptionProvider;

    #[\Override]
    protected function setUp(): void
    {
        $productDescriptionProviders = new ServiceLocator([
            self::CONFIGURATION_OPTION => function () {
                return $this->getDescriptionProvider();
            }
        ]);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::any())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::SCHEMA_ORG_DESCRIPTION_FIELD),
                false,
                false,
                null
            )
            ->willReturn(self::CONFIGURATION_OPTION);

        $this->productDescriptionProvider = new SchemaOrgProductDescriptionProvider(
            $productDescriptionProviders,
            $configManager
        );
    }

    private function getProduct(): Product
    {
        $product = new ProductStub();
        $description = $this->getProductDescription();
        $product->addDescription($description);

        return $product;
    }

    private function getProductDescription(): ProductDescription
    {
        $description = new ProductDescription();
        $description->setLocalization(new LocalizationStub(1));
        $description->setText(self::PRODUCT_DESCRIPTION);

        return $description;
    }

    private function getDescriptionProvider(): SchemaOrgProductDescriptionProviderInterface|MockObject
    {
        $provider = $this->createMock(SchemaOrgProductDescriptionProviderInterface::class);
        $provider->expects(self::any())
            ->method('getDescription')
            ->with($this->getProduct(), new LocalizationStub(1))
            ->willReturn(self::PRODUCT_DESCRIPTION);

        return $provider;
    }

    public function testGetDescription(): void
    {
        self::assertEquals(
            $this->getProductDescription()->getText(),
            $this->productDescriptionProvider->getDescription(
                $this->getProduct(),
                new LocalizationStub(1)
            )
        );
    }
}
