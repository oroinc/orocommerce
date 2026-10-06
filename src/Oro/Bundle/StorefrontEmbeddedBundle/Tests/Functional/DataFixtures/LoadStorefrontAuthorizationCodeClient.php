<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OAuth2ServerBundle\Entity\Client;
use Oro\Bundle\OAuth2ServerBundle\Entity\Manager\ClientManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A storefront OAuth application with the authorization code grant and the session transfer enabled.
 */
class LoadStorefrontAuthorizationCodeClient extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    public const string OAUTH_CLIENT_ID = 'RvQaZ8H1nXbyPk0tLuWc5jEdMoSgY7Tf';
    public const string OAUTH_CLIENT_SECRET = 'k29Xn4TzQb7pLdYw1RsHcMv6JeUgA0FoNiZxB3rSyKtOqPlD5hCu8WmVnEjT2aXG';
    public const string OAUTH_CLIENT_REDIRECT_URI = 'http://test.com';

    private ClientManager $clientManager;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->clientManager = $container->get('oro_oauth2_server.client_manager');
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $client = new Client();
        $client->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $client->setName('test storefront authorization code application');
        $client->setGrants(['authorization_code']);
        $client->setIdentifier(self::OAUTH_CLIENT_ID);
        $client->setPlainSecret(self::OAUTH_CLIENT_SECRET);
        $client->setRedirectUris([self::OAUTH_CLIENT_REDIRECT_URI]);
        $client->setFrontend(true);
        $client->setSessionTransferAllowed(true);

        $this->clientManager->updateClient($client);
    }
}
