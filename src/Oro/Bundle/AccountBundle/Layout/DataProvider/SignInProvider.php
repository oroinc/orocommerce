<?php

namespace Oro\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Security;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class SignInProvider
{
    /**
     * @var array
     */
    protected $options = [];

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
     * @return string|null
     */
    public function getLastName()
    {
        if (!array_key_exists('last_username', $this->options)) {
            $request = $this->requestStack->getCurrentRequest();
            $session = $request->getSession();
            
            // last username entered by the user
            $this->options['last_username'] = (null === $session) ? '' : $session->get(Security::LAST_USERNAME);
        }

        return $this->options['last_username'];
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        if (!array_key_exists('error', $this->options)) {
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

            $this->options['error'] = $error;
        }

        return $this->options['error'];
    }

    /**
     * @return string
     */
    public function getCSRFToken()
    {
        if (!array_key_exists('csrf_token', $this->options)) {
            $this->options['csrf_token'] = $this->csrfTokenManager->getToken('authenticate')->getValue();
        }

        return $this->options['csrf_token'];
    }

    /**
     * @return mixed|null
     */
    public function getLoggedUser()
    {
        return $this->securityFacade->getLoggedUser();
    }
}
