<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\ImportExport\Configuration\RelatedProductsImportExportConfigurationProvider;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RelatedProductsImportExportConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var RelatedItemConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedItemConfigHelper;

    /** @var RelatedProductsImportExportConfigurationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->relatedItemConfigHelper = $this->createMock(RelatedItemConfigHelper::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return 'translated ' . $key;
            });

        $this->provider = new RelatedProductsImportExportConfigurationProvider(
            $translator,
            $this->authorizationChecker,
            $this->relatedItemConfigHelper
        );
    }

    public function testGet(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_related_products_edit')
            ->willReturn(true);

        $configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->relatedItemConfigHelper->expects($this->once())
            ->method('getConfigProvider')
            ->with('related_products')
            ->willReturn($configProvider);

        $this->assertEquals(
            new ImportExportConfiguration(
                [
                    ImportExportConfiguration::FIELD_ENTITY_CLASS => RelatedProduct::class,
                    ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'related_product_export_to_csv',
                    ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_related_product',
                    ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                        'translated oro.product.export.related_products.label',
                    ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                        'oro_product_related_product_export_template',
                    ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'related_product_import_from_csv',
                    ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                        'oro_product_related_product.add_or_replace',
                    ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL =>
                        'translated oro.product.import_validation.related_products.label',
                    ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL =>
                        'translated oro.product.import.related_products.label',
                ]
            ),
            $this->provider->get()
        );
    }

    public function testGetWithoutPermission(): void
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_related_products_edit')
            ->willReturn(false);

        $configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->relatedItemConfigHelper->expects($this->once())
            ->method('getConfigProvider')
            ->with('related_products')
            ->willReturn($configProvider);

        $this->assertEquals(
            new ImportExportConfiguration(
                [
                    ImportExportConfiguration::FIELD_ENTITY_CLASS => RelatedProduct::class,
                    ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'related_product_export_to_csv',
                    ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_related_product',
                    ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                        'translated oro.product.export.related_products.label',
                ]
            ),
            $this->provider->get()
        );
    }

    public function testGetWhenFeatureIsDisabled(): void
    {
        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $configProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->relatedItemConfigHelper->expects($this->once())
            ->method('getConfigProvider')
            ->with('related_products')
            ->willReturn($configProvider);

        $this->assertEquals(new ImportExportConfiguration(), $this->provider->get());
    }
}
