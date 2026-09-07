<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Firewall\CustomerVisitorCookieFactory;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\CustomerBundle\Security\VisitorIdentifierUtil;
use Oro\Bundle\OAuth2ServerBundle\Entity\Manager\ClientManager;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Bootstraps a storefront session from an OAuth2 access token, so a host on a different origin (where the
 * browser can't forward Oro's cookies) can still render session-based embedded fragments: the host calls this
 * once with the visitor's Bearer token and reuses the returned cookies server-side.
 *
 * The token is validated by Oro's own OAuth2 resource server and only active storefront (frontend) clients are
 * accepted — mirroring how OAuth2Authenticator resolves the owner class, so a back-office token can never open
 * a storefront session. A customer user token opens a session for exactly that CustomerUser; a guest token
 * answers with the customer visitor cookie of exactly the visitor that token was issued for, which is how the
 * storefront identifies a guest. No new trust anchor, no impersonation.
 */
class EmbeddedSessionController
{
    private const string FRONTEND_FIREWALL = 'frontend';

    public function __construct(
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly AuthorizationValidatorInterface $authorizationValidator,
        private readonly ClientManager $clientManager,
        private readonly UserProviderInterface $userProvider,
        private readonly LoginManager $loginManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CustomerVisitorCookieFactory $visitorCookieFactory,
    ) {
    }

    #[Route(path: '', name: 'oro_storefront_embedded_session', methods: ['POST'])]
    public function bootstrapAction(Request $request): Response
    {
        $tokenUserIdentifier = $this->resolveTokenUserIdentifier($request);
        if (null === $tokenUserIdentifier) {
            throw new UnauthorizedHttpException('Bearer', 'A storefront OAuth2 access token is required.');
        }

        if (VisitorIdentifierUtil::isVisitorIdentifier($tokenUserIdentifier)) {
            return $this->sendCookieForVisitor($request, $tokenUserIdentifier);
        }

        return $this->openSessionForCustomerUser($tokenUserIdentifier);
    }

    private function openSessionForCustomerUser(string $userIdentifier): Response
    {
        $user = $this->loadUser($userIdentifier);
        if (!$user instanceof CustomerUser) {
            throw new UnauthorizedHttpException('Bearer', 'A storefront OAuth2 access token is required.');
        }

        $this->loginManager->logInUser(self::FRONTEND_FIREWALL, $user);
        $this->assertSessionOpenedFor($user);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function sendCookieForVisitor(Request $request, string $visitorIdentifier): Response
    {
        $request->attributes->set(
            AnonymousCustomerUserAuthenticator::COOKIE_ATTR_NAME,
            $this->visitorCookieFactory->getCookie(VisitorIdentifierUtil::decodeIdentifier($visitorIdentifier))
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function resolveTokenUserIdentifier(Request $request): ?string
    {
        $serverRequest = $this->httpMessageFactory->createRequest($request);

        try {
            $validated = $this->authorizationValidator->validateAuthorization($serverRequest);
        } catch (OAuthServerException) {
            return null;
        }

        if (!$this->isActiveStorefrontClient((string)$validated->getAttribute('oauth_client_id'))) {
            return null;
        }

        $userIdentifier = $validated->getAttribute('oauth_user_id');

        return \is_string($userIdentifier) && '' !== $userIdentifier ? $userIdentifier : null;
    }

    private function loadUser(string $userIdentifier): ?object
    {
        try {
            return $this->userProvider->loadUserByIdentifier($userIdentifier);
        } catch (AuthenticationException) {
            return null;
        }
    }

    private function isActiveStorefrontClient(string $clientId): bool
    {
        $client = $this->clientManager->getClient($clientId);

        return null !== $client && $client->isActive() && $client->isFrontend();
    }

    /**
     * LoginManager::logInUser() silently swallows account-status failures (disabled/expired), so the opened
     * session is verified before the host is told the returned cookie is usable.
     */
    private function assertSessionOpenedFor(CustomerUser $user): void
    {
        if ($this->tokenStorage->getToken()?->getUserIdentifier() !== $user->getUserIdentifier()) {
            throw new UnauthorizedHttpException('Bearer', 'The account cannot start a storefront session.');
        }
    }
}
