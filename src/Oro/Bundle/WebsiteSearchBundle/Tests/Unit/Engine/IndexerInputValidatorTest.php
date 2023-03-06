<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndexerInputValidatorTest extends \PHPUnit\Framework\TestCase
{
    use ContextTrait;

    private const WEBSITE_ID = 1;

    private IndexerInputValidator $indexerInputValidator;
    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;
    private ReindexationWebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject $reindexationWebsiteProvider;
    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    protected function setUp(): void
    {
        $mappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->reindexationWebsiteProvider = $this->createMock(ReindexationWebsiteProviderInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $mappingProvider->expects(self::any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));
        $mappingProvider->expects(self::any())
            ->method('getEntityClasses')
            ->willReturn([TestActivity::class]);

        $websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $websiteProvider->expects(self::any())
            ->method('getWebsiteIds')
            ->willReturn([101, 102]);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->indexerInputValidator = new IndexerInputValidator(
            $websiteProvider,
            $mappingProvider,
            $managerRegistry,
            $this->reindexationWebsiteProvider,
            $this->tokenAccessor
        );
    }

    public function testIncoherentEntityInput(): void
    {
        $this->expectException(\LogicException::class);
        $context = [];
        $context = $this->setContextEntityIds($context, [1, 2, 3]);
        $this->indexerInputValidator->validateRequestParameters(['class1', 'class2'], $context);
    }

    public function testConfigureEntityOptions(): void
    {
        $reference = $this->createMock(Proxy::class);
        $this->entityManager->expects(self::any())
            ->method('getReference')
            ->willReturn($reference);

        $optionsResolver = new OptionsResolver();
        $this->indexerInputValidator->configureEntityOptions($optionsResolver);

        self::assertEquals(
            ['entity' => [$reference]],
            $optionsResolver->resolve(['entity' => [['class' => Product::class, 'id' => 42]]])
        );
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testConfigureEntityOptionsWhenInvalid(array $options, string $error): void
    {
        $optionsResolver = new OptionsResolver();
        $this->indexerInputValidator->configureEntityOptions($optionsResolver);

        $this->expectExceptionMessageMatches($error);

        $optionsResolver->resolve($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            ['options' => ['invalid'], 'error' => '/The option "0" does not exist. Defined options are: "entity"./'],
            ['options' => ['entity' => []], 'error' => '/Option "entity" was not expected to be empty/'],
            [
                'options' => ['entity' => ['class' => Product::class]],
                'error' => '/The value of the option "entity" is expected to be of type array of array, '
                    . 'but is of type array of "string"./',
            ],
            [
                'options' => ['entity' => [['class' => Product::class]]],
                'error' => '/The required option "entity\[0\]\[id\]" is missing./',
            ],
            [
                'options' => ['entity' => [['id' => 42]]],
                'error' => '/The required option "entity\[0\]\[class\]" is missing./',
            ],
        ];
    }

    public function testConfigureWebsiteIdsContextOptionsWhenThereIsNoOrganizationToken(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);
        $this->reindexationWebsiteProvider->expects(self::never())
            ->method('getReindexationWebsiteIdsForOrganization');

        $optionResolver = new OptionsResolver();
        $this->indexerInputValidator->configureContextOptions($optionResolver);

        self::assertEquals(['context' => ['websiteIds' => [101, 102]]], $optionResolver->resolve([]));
    }

    public function testConfigureWebsiteIdsContextOptionsWithOrganizationInToken(): void
    {
        $organization = new Organization();

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->reindexationWebsiteProvider->expects(self::once())
            ->method('getReindexationWebsiteIdsForOrganization')
            ->with($organization)
            ->willReturn([1, 3]);

        $optionResolver = new OptionsResolver();
        $this->indexerInputValidator->configureContextOptions($optionResolver);

        self::assertEquals(['context' => ['websiteIds' => [1, 3]]], $optionResolver->resolve([]));
    }
}
