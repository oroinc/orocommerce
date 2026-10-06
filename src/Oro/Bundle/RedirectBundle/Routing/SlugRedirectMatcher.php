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
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ScopeManager */
    private $scopeManager;

    public function __construct(ManagerRegistry $doctrine, ScopeManager $scopeManager)
    {
        $this->doctrine = $doctrine;
        $this->scopeManager = $scopeManager;
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
            $contextRedirect = $repository->findByUrlAndOrganization($contextUrl, $scopeCriteria, $organization);
            $prototypeRedirect = $repository->findByPrototypeAndOrganization(
                $itemSlugPrototype,
                $scopeCriteria,
                $organization
            );
            if (null !== $contextRedirect || null !== $prototypeRedirect) {
                $contextRedirectUrl = $contextRedirect
                    ? $contextRedirect->getTo()
                    : $contextUrl;
                $prototypeUrl = $prototypeRedirect
                    ? $prototypeRedirect->getToPrototype()
                    : $itemSlugPrototype;

                $redirect = new Redirect();
                $redirect->setTo($contextRedirectUrl . $delimiter . $prototypeUrl);
                $redirect->setType(Redirect::MOVED_PERMANENTLY);

                return $redirect;
            }
        }

        return $repository->findByUrlAndOrganization($url, $scopeCriteria, $organization);
    }

    /**
     * A redirect belongs to no organization unless an application restricts it to one.
     */
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
