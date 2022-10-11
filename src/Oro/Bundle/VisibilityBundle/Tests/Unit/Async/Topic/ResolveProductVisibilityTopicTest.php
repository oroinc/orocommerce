<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Topic;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ResolveProductVisibilityTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->willReturnCallback(function (string $class) {
                return is_a($class, VisibilityInterface::class, true) || is_a($class, Product::class, true)
                    ? $this->createMock(EntityManagerInterface::class)
                    : null;
            });

        return new ResolveProductVisibilityTopic($managerRegistry);
    }

    public function validBodyDataProvider(): array
    {
        return [
            'required only' => [
                'body' => ['entity_class_name' => ProductVisibility::class, 'id' => 42],
                'expectedBody' => ['entity_class_name' => ProductVisibility::class, 'id' => 42],
            ],
            'required only with target_entity_class' => [
                'body' => [
                    'entity_class_name' => ProductVisibility::class,
                    'target_class_name' => Product::class,
                    'target_id' => 4242,
                    'scope_id' => 101,
                ],
                'expectedBody' => [
                    'entity_class_name' => ProductVisibility::class,
                    'id' => null,
                    'target_class_name' => Product::class,
                    'target_id' => 4242,
                    'scope_id' => 101,
                ],
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' => '/The required option "entity_class_name" is missing./',
            ],
            'without id and target_class_name' => [
                'body' => ['entity_class_name' => CategoryVisibility::class],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "id" is expected to be not empty./',
            ],
            'entity_class_name has invalid type' => [
                'body' => ['entity_class_name' => new \stdClass(), 'id' => 42],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entity_class_name" with value stdClass is expected '
                    . 'to be of type "string"/',
            ],
            'entity_class_name has invalid value' => [
                'body' => ['entity_class_name' => \stdClass::class, 'id' => 42],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "entity_class_name" is expected to contain a manageable class./',
            ],
            'id has invalid type' => [
                'body' => ['entity_class_name' => CategoryVisibility::class, 'id' => new \stdClass()],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "id" with value stdClass is expected to be of type "int"/',
            ],
            'with target_class_name, without target_id' => [
                'body' => ['entity_class_name' => CategoryVisibility::class, 'target_class_name' => Product::class],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "target_id" is expected to be not empty./',
            ],
            'with target_class_name, target_id, without scope_id' => [
                'body' => [
                    'entity_class_name' => CategoryVisibility::class,
                    'target_class_name' => Product::class,
                    'target_id' => 4242,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "scope_id" is expected to be not empty./',
            ],
            'target_class_name has invalid type' => [
                'body' => [
                    'entity_class_name' => CategoryVisibility::class,
                    'target_class_name' => new \stdClass(),
                    'target_id' => 4242,
                    'scope_id' => 101,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "target_class_name" with value stdClass is expected '
                    . 'to be of type "string"/',
            ],
            'target_class_name has invalid value' => [
                'body' => [
                    'entity_class_name' => CategoryVisibility::class,
                    'target_class_name' => \stdClass::class,
                    'target_id' => 4242,
                    'scope_id' => 101,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "target_class_name" is expected to contain a manageable class./',
            ],
            'target_id has invalid type' => [
                'body' => [
                    'entity_class_name' => CategoryVisibility::class,
                    'target_class_name' => Product::class,
                    'target_id' => new \stdClass(),
                    'scope_id' => 101,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "target_id" with value stdClass is expected to be of type "int"/',
            ],
            'scope_id has invalid type' => [
                'body' => [
                    'entity_class_name' => CategoryVisibility::class,
                    'target_class_name' => Product::class,
                    'target_id' => 4242,
                    'scope_id' => new \stdClass(),
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "scope_id" with value stdClass is expected to be of type "int"/',
            ],
        ];
    }
}
