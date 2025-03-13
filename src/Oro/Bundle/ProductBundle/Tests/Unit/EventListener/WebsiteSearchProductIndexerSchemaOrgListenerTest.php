<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteSearchProductIndexerSchemaOrgListenerTest extends TestCase
{
    private WebsiteContextManager&MockObject $websiteContextManager;
    private AbstractWebsiteLocalizationProvider&MockObject $websiteLocalizationProvider;
    private SchemaOrgProductDescriptionProviderInterface&MockObject $schemaOrgProductDescriptionProvider;
    private WebsiteRepository&MockObject $websiteRepository;
    private WebsiteSearchProductIndexerSchemaOrgListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->websiteLocalizationProvider = $this->createMock(AbstractWebsiteLocalizationProvider::class);
        $this->schemaOrgProductDescriptionProvider = $this->createMock(
            SchemaOrgProductDescriptionProviderInterface::class
        );
        $this->websiteRepository = $this->createMock(WebsiteRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($this->websiteRepository);

        $this->listener = new WebsiteSearchProductIndexerSchemaOrgListener(
            $this->websiteLocalizationProvider,
            $this->websiteContextManager,
            $this->schemaOrgProductDescriptionProvider,
            $doctrine
        );
    }

    private function getWebsite(): Website
    {
        $website = new WebsiteStub(1);
        $website->setOrganization(new Organization());

        return $website;
    }

    private function getBrand(): Brand
    {
        $brand = new Brand();
        $brand->setDefaultName('test_brand');
        return $brand;
    }

    public function testOnWebsiteSearchIndexProductClass(): void
    {
        $product = (new ProductStub())->setBrand($this->getBrand());
        $localization = new LocalizationStub(1);
        $website = $this->getWebsite();
        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']];

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
            'schema_org_description_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue('test_description', [LocalizationIdPlaceholder::NAME => 1]),
                    'all_text' => false
                ]
            ],
            'schema_org_brand_name_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue('test_brand', [LocalizationIdPlaceholder::NAME => 1]),
                    'all_text' => false
                ]
            ],
        ], current($event->getEntitiesData()));
    }

    public function testOnWebsiteSearchIndexProductClassWhenWebsiteNotExists(): void
    {
        $product = (new ProductStub())->setBrand($this->getBrand());
        $localization = new LocalizationStub(1);
        $website = $this->getWebsite();
        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']];

        $this->websiteContextManager->expects(self::once())
            ->method('getWebsite')
            ->with($context)
            ->willReturn(null);

        $this->websiteRepository->expects(self::once())
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
            'schema_org_description_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue('test_description', [LocalizationIdPlaceholder::NAME => 1]),
                    'all_text' => false
                ]
            ],
            'schema_org_brand_name_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue('test_brand', [LocalizationIdPlaceholder::NAME => 1]),
                    'all_text' => false
                ]
            ],
        ], current($event->getEntitiesData()));
    }
}
