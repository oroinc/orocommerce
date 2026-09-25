<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Security;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Oro\Bundle\OAuth2ServerBundle\Entity\Manager\ClientManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Resolves the storefront OAuth2 access token a request carries as its Bearer credential. The token is validated
 * by Oro's own OAuth2 resource server, and only a token of an active storefront (frontend) client of an enabled
 * organization is accepted, the client rules OAuth2Authenticator applies, so a back-office token can never open
 * a storefront session. Every refusal is the same generic 401, so the response tells nothing about which client,
 * organization or user exists; the reason goes to the log instead.
 */
class StorefrontAccessTokenResolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string AUTHENTICATION_CHALLENGE = 'Bearer';
    public const string TOKEN_REQUIRED_MESSAGE = 'A storefront OAuth2 access token is required.';

    private const string OAUTH_ACCESS_TOKEN_ID_ATTRIBUTE = 'oauth_access_token_id';
    private const string OAUTH_CLIENT_ID_ATTRIBUTE = 'oauth_client_id';
    private const string OAUTH_USER_ID_ATTRIBUTE = 'oauth_user_id';

    public function __construct(
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
        private readonly AuthorizationValidatorInterface $authorizationValidator,
        private readonly ClientManager $clientManager,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @throws UnauthorizedHttpException when the request carries no access token this endpoint accepts
     */
    public function resolve(Request $request): StorefrontAccessToken
    {
        $serverRequest = $this->httpMessageFactory->createRequest($request);
        try {
            $validatedRequest = $this->authorizationValidator->validateAuthorization($serverRequest);
        } catch (OAuthServerException $exception) {
            $this->logger->info('Embedded session bootstrap rejected the access token.', ['exception' => $exception]);

            throw $this->createTokenRequiredException($exception);
        }

        if (!$validatedRequest->getAttribute(self::OAUTH_ACCESS_TOKEN_ID_ATTRIBUTE)) {
            $this->logger->notice('Embedded session bootstrap refused an access token without an id.');

            throw $this->createTokenRequiredException();
        }

        $clientId = (string) $validatedRequest->getAttribute(self::OAUTH_CLIENT_ID_ATTRIBUTE);
        $organization = $this->resolveClientOrganization($clientId);

        $userIdentifier = $validatedRequest->getAttribute(self::OAUTH_USER_ID_ATTRIBUTE);
        if (!\is_string($userIdentifier) || '' === $userIdentifier) {
            $this->logger->notice('Embedded session bootstrap refused an access token without a user.', [
                'oauth_client_id' => $clientId,
            ]);

            throw $this->createTokenRequiredException();
        }

        return new StorefrontAccessToken($userIdentifier, $organization);
    }

    private function resolveClientOrganization(string $clientId): Organization
    {
        $client = $this->clientManager->getClient($clientId);
        if (null === $client || !$client->isActive() || !$client->isFrontend()) {
            $this->logger->notice('Embedded session bootstrap refused an inactive or non-storefront client.', [
                'oauth_client_id' => $clientId,
            ]);

            throw $this->createTokenRequiredException();
        }

        $organization = $client->getOrganization();
        if (null === $organization || !$organization->isEnabled()) {
            $this->logger->notice('Embedded session bootstrap refused a client of a disabled organization.', [
                'oauth_client_id' => $clientId,
                'organization_id' => $organization?->getId(),
            ]);

            throw $this->createTokenRequiredException();
        }

        return $organization;
    }

    private function createTokenRequiredException(?\Throwable $previous = null): UnauthorizedHttpException
    {
        return new UnauthorizedHttpException(self::AUTHENTICATION_CHALLENGE, self::TOKEN_REQUIRED_MESSAGE, $previous);
    }
}
