<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Security;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

class SignInProvider
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $csrfTokenManager;

    /**
     * @param RequestStack $requestStack
     * @param SecurityFacade $securityFacade
     * @param CsrfTokenManagerInterface $csrfTokenManager
     */
    public function __construct(
        RequestStack $requestStack,
        SecurityFacade $securityFacade,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->requestStack = $requestStack;
        $this->securityFacade = $securityFacade;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if ($this->data !== null) {
            return $this->data;
        }

        if ($this->securityFacade->getLoggedUser()) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(Security::AUTHENTICATION_ERROR)) {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(Security::LAST_USERNAME);
        $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();

        $this->data = [
            'last_username' => $lastUsername,
            'csrf_token' => $csrfToken,
            'error'=> $error,
        ];

        return $this->data;
    }
}
