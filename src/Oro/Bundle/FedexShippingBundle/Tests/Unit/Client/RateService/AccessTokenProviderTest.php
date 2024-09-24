<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Client\RateService\AccessTokenProvider;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\TestCase;

class AccessTokenProviderTest extends TestCase
{
    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject  */
    private $restClient;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject  */
    private $doctrine;

    /** @var AccessTokenProvider  */
    private $accessTokenProvider;

    #[\Override]
    protected function setUp(): void
    {
        $restClientFactory = $this->createMock(RestClientFactoryInterface::class);
        $crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->restClient = $this->createMock(RestClientInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $restClientFactory->expects(self::any())
            ->method('createRestClient')
            ->with('https:://test.com/api')
            ->willReturn($this->restClient);

        $crypter->expects(self::any())
            ->method('decryptData')
            ->willReturnCallback(function (string $inputString) {
                return $inputString . '_decrypted';
            });

        $this->accessTokenProvider = new AccessTokenProvider($restClientFactory, $crypter, $this->doctrine);
    }

    public function testGetAccessTokenInProdModeWhenTokenExistsAndValid(): void
    {
        $accessToken = 'access_token';
        $settings = new FedexIntegrationSettings();
        $settings->setAccessToken($accessToken);
        $settings->setAccessTokenExpiresAt(new \DateTime('now +2 days', new \DateTimeZone('UTC')));

        $this->restClient->expects(self::never())
            ->method('post');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertEquals(
            $accessToken,
            $this->accessTokenProvider->getAccessToken($settings, 'https:://test.com/api')
        );
    }

    public function testGetAccessTokenInProdModeWhenTokenDoNotExist(): void
    {
        $accessToken = 'access_token';
        $settings = new FedexIntegrationSettings();
        $settings->setClientId('client_id');
        $settings->setClientSecret('client_secret');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with($settings);
        $em->expects(self::once())
            ->method('flush');
        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn([
                'access_token' => $accessToken,
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'scope' => 'CXS'
            ]);

        $this->restClient->expects(self::once())
            ->method('post')
            ->with(
                '/oauth/token',
                'grant_type=client_credentials&client_id=client_id&client_secret=client_secret_decrypted',
                [
                    'Content-length' => 87,
                    'content-type'   => 'application/x-www-form-urlencoded',
                    'user-agent'     => 'oro-oauth'
                ]
            )
            ->willReturn($response);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($em);

        self::assertEquals(
            $accessToken,
            $this->accessTokenProvider->getAccessToken($settings, 'https:://test.com/api')
        );
        self::assertEquals($accessToken, $settings->getAccessToken());
        self::assertInstanceOf(\DateTime::class, $settings->getAccessTokenExpiresAt());
    }

    public function testGetAccessTokenInCheckModeWhenTokenIsExpired(): void
    {
        $accessToken = 'access_token';
        $settings = new FedexIntegrationSettings();
        $settings->setClientId('client_id');
        $settings->setClientSecret('client_secret');
        $settings->setAccessToken('old_token');
        $settings->setAccessTokenExpiresAt(new \DateTime('now -2 days', new \DateTimeZone('UTC')));

        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn([
                'access_token' => $accessToken,
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'scope' => 'CXS'
            ]);

        $this->restClient->expects(self::once())
            ->method('post')
            ->with(
                '/oauth/token',
                'grant_type=client_credentials&client_id=client_id&client_secret=client_secret_decrypted',
                [
                    'Content-length' => 87,
                    'content-type'   => 'application/x-www-form-urlencoded',
                    'user-agent'     => 'oro-oauth'
                ]
            )
            ->willReturn($response);
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertEquals(
            $accessToken,
            $this->accessTokenProvider->getAccessToken($settings, 'https:://test.com/api', true)
        );
        self::assertEquals($accessToken, $settings->getAccessToken());
        self::assertInstanceOf(\DateTime::class, $settings->getAccessTokenExpiresAt());
    }

    public function testTryToGetAccessTokenWhenResultHaveNoAccessToken(): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionMessage('Access token was not get.');

        $settings = new FedexIntegrationSettings();
        $settings->setClientId('client_id');
        $settings->setClientSecret('client_secret');

        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::exactly(4))
            ->method('json')
            ->willReturn([
                'error' => 'some_error'
            ]);

        $this->restClient->expects(self::exactly(4))
            ->method('post')
            ->with(
                '/oauth/token',
                'grant_type=client_credentials&client_id=client_id&client_secret=client_secret_decrypted',
                [
                    'Content-length' => 87,
                    'content-type'   => 'application/x-www-form-urlencoded',
                    'user-agent'     => 'oro-oauth'
                ]
            )
            ->willReturn($response);
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->accessTokenProvider->getAccessToken($settings, 'https:://test.com/api', true);
    }
}
