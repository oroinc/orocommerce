<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentBundle\Provider\AvailablePaymentStatusesCacheKeyProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AvailablePaymentStatusesCacheKeyProviderTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private TokenSerializerInterface&MockObject $tokenSerializer;
    private AvailablePaymentStatusesCacheKeyProvider $provider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenSerializer = $this->createMock(TokenSerializerInterface::class);
        $this->provider = new AvailablePaymentStatusesCacheKeyProvider(
            $this->tokenAccessor,
            $this->tokenSerializer
        );
    }

    public function testGetCacheKeyWithToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'serialized_token_data';
        $entityClass = 'App\Entity\Order';

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->expects(self::once())
            ->method('serialize')
            ->with($token)
            ->willReturn($serializedToken);

        $result = $this->provider->getCacheKey($entityClass);
        $expectedHash = sha1($serializedToken);
        $normalizedEntityClass = rawurlencode($entityClass);
        $expected = sprintf('%s|%s', $normalizedEntityClass, $expectedHash);

        self::assertEquals($expected, $result);
        self::assertStringContainsString('%5C', $result); // \ encoded
    }

    public function testGetCacheKeyWithNullToken(): void
    {
        $entityClass = 'App\Entity\Order';

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->tokenSerializer
            ->expects(self::never())
            ->method('serialize');

        $result = $this->provider->getCacheKey($entityClass);
        $expectedHash = sha1('none');
        $normalizedEntityClass = rawurlencode($entityClass);
        $expected = sprintf('%s|%s', $normalizedEntityClass, $expectedHash);

        self::assertEquals($expected, $result);
        self::assertStringContainsString('%5C', $result); // \ encoded
    }

    public function testGetCacheKeyWithTokenSerializationException(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $entityClass = 'App\Entity\Order';

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->expects(self::once())
            ->method('serialize')
            ->with($token)
            ->willThrowException(new InvalidTokenSerializationException('Serialization failed'));

        $result = $this->provider->getCacheKey($entityClass);
        $expectedHash = sha1('none');
        $normalizedEntityClass = rawurlencode($entityClass);
        $expected = sprintf('%s|%s', $normalizedEntityClass, $expectedHash);

        self::assertEquals($expected, $result);
        self::assertStringContainsString('%5C', $result); // \ encoded
    }

    public function testGetCacheKeyWithDifferentEntityClasses(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'same_token_data';

        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->expects(self::exactly(2))
            ->method('serialize')
            ->with($token)
            ->willReturn($serializedToken);

        $result1 = $this->provider->getCacheKey('App\Entity\Order');
        $result2 = $this->provider->getCacheKey('App\Entity\Invoice');

        self::assertNotEquals($result1, $result2);
        self::assertStringStartsWith(rawurlencode('App\Entity\Order') . '|', $result1);
        self::assertStringStartsWith(rawurlencode('App\Entity\Invoice') . '|', $result2);
        self::assertStringContainsString('%5C', $result1); // \ encoded
        self::assertStringContainsString('%5C', $result2); // \ encoded
    }

    public function testGetCacheKeyWithSameTokenProducesSameResult(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'same_token_data';
        $entityClass = 'App\Entity\Order';

        $this->tokenAccessor
            ->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->expects(self::exactly(2))
            ->method('serialize')
            ->with($token)
            ->willReturn($serializedToken);

        $result1 = $this->provider->getCacheKey($entityClass);
        $result2 = $this->provider->getCacheKey($entityClass);

        self::assertEquals($result1, $result2);
    }

    public function testGetCacheKeyWithOrganizationAwareToken(): void
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $serializedToken = 'organizationId=1;userId=10;userClass=User;roles=USER_ROLE_BASIC';
        $entityClass = 'App\Entity\Order';

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->expects(self::once())
            ->method('serialize')
            ->with($token)
            ->willReturn($serializedToken);

        $result = $this->provider->getCacheKey($entityClass);
        $expectedHash = sha1($serializedToken);
        $normalizedEntityClass = rawurlencode($entityClass);
        $expected = sprintf('%s|%s', $normalizedEntityClass, $expectedHash);

        self::assertEquals($expected, $result);
        self::assertStringContainsString('%5C', $result); // \ encoded
    }

    public function testGetCacheKeyWithReservedCharacters(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'test_token';
        $entityClass = 'App\Entity\Test{with}reserved:characters@';

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->expects(self::once())
            ->method('serialize')
            ->with($token)
            ->willReturn($serializedToken);

        $result = $this->provider->getCacheKey($entityClass);
        $expectedHash = sha1($serializedToken);
        $encodedEntityClass = rawurlencode($entityClass);
        $expected = sprintf('%s|%s', $encodedEntityClass, $expectedHash);

        self::assertEquals($expected, $result);
        self::assertStringContainsString('%7B', $result); // { encoded
        self::assertStringContainsString('%7D', $result); // } encoded
        self::assertStringContainsString('%3A', $result); // : encoded
        self::assertStringContainsString('%40', $result); // @ encoded
        self::assertStringContainsString('%5C', $result); // \ encoded (from 'App\Entity\Test')
    }

    public function testGetCacheKeyWithoutReservedCharacters(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $serializedToken = 'test_token';
        $entityClass = 'AppEntityTest';  // No reserved characters

        $this->tokenAccessor
            ->method('getToken')
            ->willReturn($token);

        $this->tokenSerializer
            ->method('serialize')
            ->with($token)
            ->willReturn($serializedToken);

        $result = $this->provider->getCacheKey($entityClass);
        $expectedHash = sha1($serializedToken);
        $expected = sprintf('%s|%s', $entityClass, $expectedHash);

        self::assertEquals($expected, $result);
        // Should not contain encoded characters since entityClass has no reserved chars
        self::assertStringStartsWith('AppEntityTest|', $result);
    }
}
