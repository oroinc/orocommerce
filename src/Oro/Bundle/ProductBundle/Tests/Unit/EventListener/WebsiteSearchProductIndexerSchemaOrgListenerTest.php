<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerSchemaOrgListener;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionProviderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Brand;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class WebsiteSearchProductIndexerSchemaOrgListenerTest extends \PHPUnit\Framework\TestCase
{
    private WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject $websiteContextManager;

    private AbstractWebsiteLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject $websiteLocalizationProvider;

    private SchemaOrgProductDescriptionProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $schemaOrgProductDescriptionProvider;

    private WebsiteRepository|\PHPUnit\Framework\MockObject\MockObject $websiteRepository;

    private WebsiteSearchProductIndexerSchemaOrgListener $listener;

    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->websiteLocalizationProvider = $this->createMock(AbstractWebsiteLocalizationProvider::class);
        $this->schemaOrgProductDescriptionProvider = $this->createMock(
            SchemaOrgProductDescriptionProviderInterface::class
        );
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->websiteRepository = $this->createMock(WebsiteRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->websiteRepository);

        $this->brand = new Brand();

        $this->listener = new WebsiteSearchProductIndexerSchemaOrgListener(
            $this->websiteLocalizationProvider,
            $this->websiteContextManager,
            $this->schemaOrgProductDescriptionProvider,
            $managerRegistry
        );
    }

    /**
     * @dataProvider listenerDataProvider
     */
    public function testOnWebsiteSearchIndexProductClass(
        Product $product,
        Localization $localization,
        Website $website,
        array $context,
    ): void {
        $this->websiteContextManager->expects(self::once())
            ->method('getWebsite')
            ->with($context)
            ->willReturn($website);

        $this->websiteLocalizationProvider->expects(self::once())
            ->method('getLocalizations')
            ->with($website)
            ->willReturn([$localization]);

        $this->schemaOrgProductDescriptionProvider->expects(self::once())
            ->method('getDescription')
            ->with($product, $localization, $website)
            ->willReturn('test_description');

        $event = new IndexEntityEvent(Product::class, [$product], $context);
        $this->listener->onWebsiteSearchIndex($event);

        self::assertEquals([
            "schema_org_description_LOCALIZATION_ID" => [
                [
                    "value" => new PlaceholderValue("test_description", [LocalizationIdPlaceholder::NAME => 1]),
                    "all_text" => false,
                ],
            ],
            "schema_org_brand_name_LOCALIZATION_ID" => [
                [
                    "value" => new PlaceholderValue("test_brand", [LocalizationIdPlaceholder::NAME => 1]),
                    "all_text" => false,
                ],
            ],
        ], current($event->getEntitiesData()));
    }

    /**
     * @dataProvider listenerDataProvider
     */
    public function testOnWebsiteSearchIndexProductClassWhenWebsiteNotExists(
        Product $product,
        Localization $localization,
        Website $website,
        array $context,
    ): void {
        $this->websiteContextManager->expects(self::once())
            ->method('getWebsite')
            ->with($context)
            ->willReturn(null);

        $this->websiteRepository
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($this->getWebsite());

        $this->websiteLocalizationProvider->expects(self::once())
            ->method('getLocalizations')
            ->with($website)
            ->willReturn([$localization]);

        $this->schemaOrgProductDescriptionProvider->expects(self::once())
            ->method('getDescription')
            ->with($product, $localization)
            ->willReturn('test_description');

        $event = new IndexEntityEvent(Product::class, [$product], $context);
        $this->listener->onWebsiteSearchIndex($event);

        self::assertEquals([
            "schema_org_description_LOCALIZATION_ID" => [
                [
                    "value" => new PlaceholderValue("test_description", [LocalizationIdPlaceholder::NAME => 1]),
                    "all_text" => false,
                ],
            ],
            "schema_org_brand_name_LOCALIZATION_ID" => [
                [
                    "value" => new PlaceholderValue("test_brand", [LocalizationIdPlaceholder::NAME => 1]),
                    "all_text" => false,
                ],
            ],
        ], current($event->getEntitiesData()));
    }

    public function listenerDataProvider(): array
    {
        return [
            'pass' => [
                'product' => (new ProductStub())->setBrand($this->getBrand()),
                'localization' => new LocalizationStub(1),
                'website' => $this->getWebsite(),
                'context' => [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']],
            ],
        ];
    }

    private function getWebsite(): Website
    {
        $organization = new Organization();
        $website = new WebsiteStub(1);
        $website->setOrganization($organization);

        return $website;
    }

    private function getBrand(): Brand
    {
        $brand = new Brand();
        $brand->setDefaultName('test_brand');
        return $brand;
    }
}
