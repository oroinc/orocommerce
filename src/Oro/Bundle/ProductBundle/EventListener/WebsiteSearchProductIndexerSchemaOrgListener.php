<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\SchemaOrgProductDescriptionProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Event listener provides product description and brand name into product search index to use on product list page
 */
class WebsiteSearchProductIndexerSchemaOrgListener implements WebsiteSearchProductIndexerListenerInterface
{
    private AbstractWebsiteLocalizationProvider $websiteLocalizationProvider;

    private WebsiteContextManager $websiteContextManager;

    private SchemaOrgProductDescriptionProviderInterface $descriptionSchemaOrgProvider;

    private ManagerRegistry $managerRegistry;

    public function __construct(
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager,
        SchemaOrgProductDescriptionProviderInterface $descriptionSchemaOrgProvider,
        ManagerRegistry $managerRegistry
    ) {
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager = $websiteContextManager;
        $this->descriptionSchemaOrgProvider = $descriptionSchemaOrgProvider;
        $this->managerRegistry = $managerRegistry;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        /** @var Product[] $products */
        $products = $event->getEntities();
        $website = $this->getWebsite($event->getContext());
        $localizations = $this->websiteLocalizationProvider->getLocalizations($website);
        foreach ($products as $product) {
            $productId = $product->getId();
            foreach ($localizations as $localization) {
                $localizationPlaceholder = [LocalizationIdPlaceholder::NAME => $localization->getId()];
                $event->addPlaceholderField(
                    $productId,
                    'schema_org_description_LOCALIZATION_ID',
                    $this->descriptionSchemaOrgProvider->getDescription($product, $localization, $website),
                    $localizationPlaceholder
                )->addPlaceholderField(
                    $productId,
                    'schema_org_brand_name_LOCALIZATION_ID',
                    (string)$product->getBrand()?->getName($localization)?->getString(),
                    $localizationPlaceholder
                );
            }
        }
    }

    private function getWebsite(array $context): Website
    {
        $website = $this->websiteContextManager->getWebsite($context);
        if (!$website) {
            $repository = $this->managerRegistry->getRepository(Website::class);
            $website = $repository->getDefaultWebsite();
        }

        return $website;
    }
}
