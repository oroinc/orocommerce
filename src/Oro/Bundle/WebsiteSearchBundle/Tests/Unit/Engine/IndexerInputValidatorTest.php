<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndexerInputValidatorTest extends \PHPUnit\Framework\TestCase
{
    use ContextTrait;

    private const WEBSITE_ID = 1;

    private IndexerInputValidator $indexerInputValidator;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $mappingProvider = $this->createMock(SearchMappingProvider::class);

        $mappingProvider
            ->expects(self::any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));
        $mappingProvider
            ->expects(self::any())
            ->method('getEntityClasses')
            ->willReturn([TestActivity::class]);

        $websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $websiteProvider
            ->expects(self::any())
            ->method('getWebsiteIds')
            ->willReturn([101, 102]);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->indexerInputValidator = new IndexerInputValidator($websiteProvider, $mappingProvider);
        $this->indexerInputValidator->setManagerRegistry($managerRegistry);
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
        $this->entityManager
            ->expects(self::any())
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
}
