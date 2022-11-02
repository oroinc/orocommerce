<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Doctrine\Persistence\ManagerRegistry;
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
    private function getApplicableRedirect($url): ?Redirect
    {
        $scopeCriteria = $this->scopeManager->getCriteria('web_content');
        $delimiter = sprintf('/%s/', SluggableUrlGenerator::CONTEXT_DELIMITER);
        $repository = $this->getRedirectRepository();
        if (str_contains($url, $delimiter)) {
            [$contextUrl, $itemSlugPrototype] = explode($delimiter, $url);
            $contextRedirect = $repository->findByUrl($contextUrl, $scopeCriteria);
            $prototypeRedirect = $repository->findByPrototype($itemSlugPrototype, $scopeCriteria);
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

        return $repository->findByUrl($url, $scopeCriteria);
    }

    private function getRedirectRepository(): RedirectRepository
    {
        return $this->doctrine
            ->getManagerForClass(Redirect::class)
            ->getRepository(Redirect::class);
    }
}
