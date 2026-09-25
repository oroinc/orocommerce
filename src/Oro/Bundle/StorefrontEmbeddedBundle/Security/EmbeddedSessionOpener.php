<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Security;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Firewall\CustomerVisitorCookieFactory;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\CustomerBundle\Security\VisitorIdentifierUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Opens the storefront session a validated storefront access token stands for, on the current website, which has
 * to belong to the organization of the token. A customer user token opens a session for exactly that
 * CustomerUser, once it belongs to that organization and passes the account checks the storefront login runs
 * (enabled, confirmed, has a customer and an owner); LoginManager alone would silently skip them. A guest token
 * answers with the customer visitor cookie of exactly the visitor the token was issued for, which is how the
 * storefront identifies a guest. No new trust anchor, no impersonation.
 */
class EmbeddedSessionOpener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string FRONTEND_FIREWALL = 'frontend';
    private const string SESSION_REFUSED_MESSAGE = 'The account cannot start a storefront session.';

    public function __construct(
        private readonly WebsiteManager $websiteManager,
        private readonly UserProviderInterface $userProvider,
        private readonly UserCheckerInterface $userChecker,
        private readonly LoginManager $loginManager,
        private readonly CustomerVisitorCookieFactory $visitorCookieFactory,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @throws UnauthorizedHttpException when no storefront session can be opened for the token
     */
    public function open(Request $request, StorefrontAccessToken $accessToken): void
    {
        $this->denyUnlessWebsiteBelongsTo($accessToken->organization);

        if (VisitorIdentifierUtil::isVisitorIdentifier($accessToken->userIdentifier)) {
            $this->sendCookieForVisitor($request, $accessToken->userIdentifier);

            return;
        }

        $this->openSessionForCustomerUser($accessToken);
    }

    private function denyUnlessWebsiteBelongsTo(Organization $organization): void
    {
        $website = $this->websiteManager->getCurrentWebsite();
        if (null === $website || $website->getOrganization()?->getId() !== $organization->getId()) {
            $this->logger->notice('Embedded session bootstrap refused a token of another organization.', [
                'organization_id' => $organization->getId(),
                'website_id' => $website?->getId(),
            ]);

            throw $this->createTokenRequiredException();
        }
    }

    private function openSessionForCustomerUser(StorefrontAccessToken $accessToken): void
    {
        $user = $this->loadUser($accessToken->userIdentifier);
        if (!$user instanceof CustomerUser) {
            throw $this->createTokenRequiredException();
        }

        if (!$user->isBelongToOrganization($accessToken->organization)) {
            $this->logger->notice('Embedded session bootstrap refused a customer user of another organization.', [
                'customer_user_id' => $user->getId(),
                'organization_id' => $accessToken->organization->getId(),
            ]);

            throw $this->createTokenRequiredException();
        }

        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AccountStatusException $exception) {
            $this->logger->notice('Embedded session bootstrap refused an account that failed the user checker.', [
                'exception' => $exception,
                'customer_user_id' => $user->getId(),
            ]);

            throw new UnauthorizedHttpException(
                StorefrontAccessTokenResolver::AUTHENTICATION_CHALLENGE,
                self::SESSION_REFUSED_MESSAGE,
                $exception
            );
        }

        $this->loginManager->logInUser(self::FRONTEND_FIREWALL, $user);
    }

    private function loadUser(string $userIdentifier): ?UserInterface
    {
        try {
            return $this->userProvider->loadUserByIdentifier($userIdentifier);
        } catch (AuthenticationException $exception) {
            $this->logger->notice('Embedded session bootstrap could not load the token user.', [
                'exception' => $exception,
            ]);

            return null;
        }
    }

    private function sendCookieForVisitor(Request $request, string $visitorIdentifier): void
    {
        $request->attributes->set(
            AnonymousCustomerUserAuthenticator::COOKIE_ATTR_NAME,
            $this->visitorCookieFactory->getCookie(VisitorIdentifierUtil::decodeIdentifier($visitorIdentifier))
        );

        $this->logger->info('Embedded session bootstrap issued a customer visitor cookie.');
    }

    private function createTokenRequiredException(): UnauthorizedHttpException
    {
        return new UnauthorizedHttpException(
            StorefrontAccessTokenResolver::AUTHENTICATION_CHALLENGE,
            StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE
        );
    }
}
