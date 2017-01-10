<?php

namespace Oro\Bundle\CustomerBundle\Security\Http\Firewall;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class AnonymousAuthenticationListener implements ListenerInterface
{
    const ANONYMOUS_CUSTOMER_USER_ROLE = 'ROLE_FRONTEND_ANONYMOUS';

    /**
     * @var ListenerInterface
     */
    private $baseListener;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @param ListenerInterface $baseListener
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface|null $logger
     * @param AuthenticationManagerInterface|null $authenticationManager
     */
    public function __construct(
        ListenerInterface $baseListener,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger = null,
        AuthenticationManagerInterface $authenticationManager = null
    ) {
        $this->baseListener = $baseListener;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $this->baseListener->handle($event);
        $token = $this->tokenStorage->getToken();
        if ($token instanceof AnonymousToken) {
            try {
                $newToken = new AnonymousToken(
                    $token->getSecret(),
                    $token->getUser(),
                    array_merge([self::ANONYMOUS_CUSTOMER_USER_ROLE], $token->getRoles())
                );
                if (null !== $this->authenticationManager) {
                    $newToken = $this->authenticationManager->authenticate($newToken);
                }

                $this->tokenStorage->setToken($newToken);

                if (null !== $this->logger) {
                    $this->logger->info('Populated the TokenStorage with an frontend anonymous Token.');
                }
            } catch (AuthenticationException $e) {
                if (null !== $this->logger) {
                    $this->logger->info('Frontend anonymous authentication failed.', ['exception' => $e]);
                }
            }
        }
    }
}
