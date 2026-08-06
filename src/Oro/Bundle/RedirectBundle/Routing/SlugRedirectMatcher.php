<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * Performs URL matching to check whether the URL is known slug to redirect.
 */
class SlugRedirectMatcher
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        protected ScopeManager $scopeManager
    ) {
    }

    /**
     * @param string $pathInfo
     *
     * @return array|null ['pathInfo' => string, 'statusCode' => int]
     */
    public function match(string $pathInfo): ?array
    {
        if ('/' !== $pathInfo) {
            $pathInfo = rtrim($pathInfo, '/');
        }

        $redirect = $this->getApplicableRedirect($pathInfo);
        if (null === $redirect) {
            return null;
        }

        return [
            'pathInfo'   => $redirect->getTo(),
            'statusCode' => $redirect->getType()
        ];
    }

    /**
     * @param string $url
     *
     * @return Redirect|null
     */
    protected function getApplicableRedirect($url): ?Redirect
    {
        $scopeCriteria = $this->scopeManager->getCriteria('web_content');
        $organization = $this->getOrganization();
        $delimiter = sprintf('/%s/', SluggableUrlGenerator::CONTEXT_DELIMITER);
        $repository = $this->getRedirectRepository();
        if (str_contains($url, $delimiter)) {
            [$contextUrl, $itemSlugPrototype] = explode($delimiter, $url);
            $contextRedirect = $repository->findByUrl($contextUrl, $scopeCriteria, $organization);
            $prototypeRedirect = $repository->findByPrototype($itemSlugPrototype, $scopeCriteria, $organization);
            if (null !== $contextRedirect || null !== $prototypeRedirect) {
                return $this->createContextRedirect(
                    $contextRedirect ? $contextRedirect->getTo() : $contextUrl,
                    $prototypeRedirect ? $prototypeRedirect->getToPrototype() : $itemSlugPrototype,
                    $delimiter
                );
            }
        }

        return $repository->findByUrl($url, $scopeCriteria, $organization);
    }

    protected function createContextRedirect(string $contextUrl, string $prototypeUrl, string $delimiter): Redirect
    {
        $redirect = new Redirect();
        $redirect->setTo($contextUrl . $delimiter . $prototypeUrl);
        $redirect->setType(Redirect::MOVED_PERMANENTLY);

        return $redirect;
    }

    protected function getOrganization(): ?Organization
    {
        return null;
    }

    protected function getRedirectRepository(): RedirectRepository
    {
        return $this->doctrine
            ->getManagerForClass(Redirect::class)
            ->getRepository(Redirect::class);
    }
}
