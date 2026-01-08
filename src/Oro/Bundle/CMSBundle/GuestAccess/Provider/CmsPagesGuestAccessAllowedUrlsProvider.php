<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\GuestAccess\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides guest access allowed URLs for configured CMS pages.
 */
class CmsPagesGuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    public function __construct(
        protected ConfigManager $configManager,
        protected RouterInterface $router,
        protected ManagerRegistry $doctrine
    ) {
    }

    public function getAllowedUrlsPatterns(): array
    {
        $patterns = [];
        $cmsPageIds = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::GUEST_ACCESS_ALLOWED_CMS_PAGES)
        );

        if (!\is_array($cmsPageIds)) {
            return $patterns;
        }

        $pageRepository = $this->doctrine->getRepository(Page::class);
        foreach ($cmsPageIds as $pageId) {
            $page = $pageRepository->find($pageId);
            if (!$page) {
                // Skip pages that don't exist
                continue;
            }

            $url = $this->router->generate('oro_cms_frontend_page_view', ['id' => $pageId]);
            $patterns[] = '^' . \preg_quote($url) . '$';
        }

        return $patterns;
    }
}
