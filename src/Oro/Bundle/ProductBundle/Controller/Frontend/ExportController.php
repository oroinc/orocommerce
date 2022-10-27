<?php

namespace Oro\Bundle\ProductBundle\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles export requests from the storefront.
 */
class ExportController extends AbstractController
{
    /**
     * @Route("/", name="oro_product_frontend_export", methods={"POST"})
     * @CsrfProtection()
     */
    public function exportAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        if (!$user instanceof CustomerUser) {
            throw new BadRequestHttpException('Current User is not defined');
        }

        /** @var Website $currentWebsite */
        $currentWebsite = $this->get(WebsiteManager::class)->getCurrentWebsite();
        $currentLocalization = $this->get(LocalizationHelper::class)->getCurrentLocalization();
        $localizationId = $currentLocalization ? $currentLocalization->getId() : null;
        $currentCurrency = $this->get(UserCurrencyManager::class)->getUserCurrency($currentWebsite);
        $refererUrl = $this->getRefererUrl($request, $currentWebsite);

        $options = [
            'filteredResultsGrid'   => 'frontend-product-search-grid',
            'currentLocalizationId' => $localizationId,
            'currentCurrency'       => $currentCurrency
        ];

        $requestOptions = $this->getOptionsFromRequest($request);
        $gridRequestParams = $this->getGridRequestParams($requestOptions);

        if ($gridRequestParams) {
            $options['filteredResultsGridParams'] = $gridRequestParams;
        }

        $this->get(MessageProducerInterface::class)->send(PreExportTopic::getName(), [
            'jobName' => 'filtered_frontend_product_export_to_csv',
            'processorAlias' => 'oro_product_frontend_product_listing',
            'outputFilePrefix' => 'product',
            'refererUrl' => $refererUrl,
            'options' => $options,
            'userId' => $user->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    private function getOptionsFromRequest(Request $request): array
    {
        $options = $request->get('options', []);

        if (!is_array($options)) {
            throw new BadRequestHttpException('Request parameter "options" must be array.');
        }

        return $options;
    }

    private function getRefererUrl(Request $request, Website $website): string
    {
        $referer = $this->get(SameSiteUrlHelper::class)->getSameSiteReferer($request);
        $baseUrl = $this->get(WebsiteUrlResolver::class)->getWebsiteUrl($website, true);

        return str_replace($baseUrl, '', $referer);
    }

    private function getGridRequestParams(array $requestOptions): ?string
    {
        if (array_key_exists('filteredResultsGridParams', $requestOptions)) {
            if (!is_string($requestOptions['filteredResultsGridParams'])) {
                throw new BadRequestHttpException('Request parameter "filteredResultsGridParams" must be string.');
            }

            return $requestOptions['filteredResultsGridParams'];
        }

        return null;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                WebsiteManager::class,
                MessageProducerInterface::class,
                LocalizationHelper::class,
                UserCurrencyManager::class,
                WebsiteUrlResolver::class,
                SameSiteUrlHelper::class,
            ]
        );
    }
}
