<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\Helper\RelatedItemConfigHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Related Products Import/Export configuration
 */
class RelatedProductsImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var RelatedItemConfigHelper */
    private $relatedItemConfigHelper;

    public function __construct(
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        RelatedItemConfigHelper $relatedItemConfigHelper
    ) {
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->relatedItemConfigHelper = $relatedItemConfigHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        $provider = $this->relatedItemConfigHelper->getConfigProvider('related_products');
        if (!$provider->isEnabled()) {
            return new ImportExportConfiguration();
        }

        $parameters = [
            ImportExportConfiguration::FIELD_ENTITY_CLASS => RelatedProduct::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'related_product_export_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_related_product',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => $this->translator->trans(
                'oro.product.export.related_products.label'
            ),
        ];

        if ($this->authorizationChecker->isGranted('oro_related_products_edit')) {
            $parameters = array_merge(
                $parameters,
                [
                    ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                        'oro_product_related_product_export_template',
                    ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'related_product_import_from_csv',
                    ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                        'oro_product_related_product.add_or_replace',
                    ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL => $this->translator->trans(
                        'oro.product.import_validation.related_products.label'
                    ),
                    ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL => $this->translator->trans(
                        'oro.product.import.related_products.label'
                    ),
                ]
            );
        }

        return new ImportExportConfiguration($parameters);
    }
}
