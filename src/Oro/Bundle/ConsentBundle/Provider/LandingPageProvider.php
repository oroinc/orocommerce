<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

/**
 * Datagrid provider that helps to show Landing Page titles by their ids on datagrid cell
 */
class LandingPageProvider
{
    private ManagerRegistry $doctrine;
    private LocalizationHelper $localizationHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        LocalizationHelper $localizationHelper
    ) {
        $this->doctrine = $doctrine;
        $this->localizationHelper = $localizationHelper;
    }

    public function getLandingPages(string $variantIds = null): string
    {
        if (!$variantIds) {
            return '';
        }

        $pages = [];
        $contentRepository = $this->doctrine->getRepository(ContentVariant::class);
        $contentVariants = $contentRepository->findBy([
            'id'   => explode(',', $variantIds),
            'type' => CmsPageContentVariantType::TYPE
        ]);
        foreach ($contentVariants as $variant) {
            $cmsPage = $variant->getCmsPage();
            if ($cmsPage instanceof Page) {
                $pages[] = $this->localizationHelper->getLocalizedValue($cmsPage->getTitles());
            }
        }

        $pagesValue = implode(', ', $pages);

        return !empty($pagesValue) ? $pagesValue : '';
    }
}
