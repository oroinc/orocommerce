<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Datagrid provider that helps to show Landing Page titles by their ids on datagrid cell
 */
class LandingPageProvider
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param ManagerRegistry $doctrine
     * @param LocalizationHelper $localizationHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LocalizationHelper $localizationHelper,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->localizationHelper = $localizationHelper;
        $this->translator = $translator;
    }

    /**
     * @param string $variantIds
     *
     * @return string
     */
    public function getLandingPages(string $variantIds = null)
    {
        if (!$variantIds) {
            return $this->translator->trans('oro.consent.content_source.none');
        }

        $pages = [];
        $contentRepository = $this->doctrine
            ->getManagerForClass(ContentVariant::class)
            ->getRepository(ContentVariant::class);

        $variantIds = explode(',', $variantIds);
        $contentVariants = $contentRepository->findBy(['id' => $variantIds, 'type' => CmsPageContentVariantType::TYPE]);
        foreach ($contentVariants as $variant) {
            $cmsPage = $variant->getCmsPage();
            if ($cmsPage instanceof Page) {
                $pages[] = $this->localizationHelper->getLocalizedValue($cmsPage->getTitles());
            }
        }

        $pagesValue = implode(', ', $pages);
        return !empty($pagesValue) ? $pagesValue : $this->translator->trans('oro.consent.content_source.none');
    }
}
