<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\DataTransformer\ConsentCollectionTransformer;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentCollectionTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConsentCollectionTransformer */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $consentConfigConverter = new ConsentConfigConverter($this->doctrine);
        $this->transformer = new ConsentCollectionTransformer($consentConfigConverter);
    }

    /**
     * @param Consent[] $consents
     * @param array $input
     * @param array|null $output
     * @dataProvider transformDataProvider
     */
    public function testTransform(array $consents, array $input, ?array $output)
    {
        $repository = $this->createMock(EntityRepository::class);
        $entityManager = $this->createMock(ObjectManager::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Consent::class)
            ->willReturn($entityManager);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Consent::class)
            ->willReturn($repository);

        $consentIds = [];
        foreach ($consents as $consent) {
            $consentIds[] = $consent->getId();
        }
        $repository->expects($this->any())
            ->method('findBy')
            ->with(['id' => $consentIds])
            ->willReturn($consents);

        $this->assertEquals($output, $this->transformer->transform($input));
    }

    public function transformDataProvider(): array
    {
        $consent34 = $this->getEntity(Consent::class, ['id' => 34]);
        $consent42 = $this->getEntity(Consent::class, ['id' => 42]);

        return [
            'empty data' => [
                'consents' => [],
                'input' => [],
                'output' => null,
            ],
            'full data' => [
                'consents' => [$consent34, $consent42],
                'input' => [
                    [
                        'consent' => 34,
                        'sort_order' => 2,
                    ],
                    [
                        'consent' => 42,
                        'sort_order' => 1,
                    ],
                ],
                'output' => [
                    new ConsentConfig($consent42, 1),
                    new ConsentConfig($consent34, 2),
                ],
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(array $input, ?array $output)
    {
        $this->assertEquals($output, $this->transformer->reverseTransform($input));
    }

    public function reverseTransformDataProvider(): array
    {
        $consent34 = $this->getEntity(Consent::class, ['id' => 34]);
        $consent42 = $this->getEntity(Consent::class, ['id' => 42]);

        return [
            'empty data' => [
                'input' => [],
                'output' => [],
            ],
            'data with empty entries' => [
                'input' => [
                    new ConsentConfig(null, 1),
                    new ConsentConfig($consent34, 2)
                ],
                'output' => [
                    [
                        'consent' => 34,
                        'sort_order' => 2,
                    ]
                ]
            ],
            'full data' => [
                'input' => [
                    new ConsentConfig($consent34, 2),
                    new ConsentConfig($consent42, 1),
                ],
                'output' => [
                    [
                        'consent' => 34,
                        'sort_order' => 2,
                    ],
                    [
                        'consent' => 42,
                        'sort_order' => 1,
                    ],
                ],
            ],
        ];
    }
}
