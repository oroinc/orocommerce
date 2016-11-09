<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class VisibilityMessageFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var VisibilityMessageFactory
     */
    protected $visibilityMessageFactory;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $visibility = $this->getEntity(
            ProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'hidden',
                'product' => $product,
                'scope' => $scope,
            ]
        );

        $visibilityRepository = $this->getMock(ObjectRepository::class);
        $visibilityRepository->method('find')->willReturnMap(
            [
                [1, $visibility],
                [2, null],
            ]
        );

        $scopeRepository = $this->getMock(ObjectRepository::class);
        $scopeRepository->method('find')->willReturnMap(
            [
                [1, $scope],
                [2, null],
            ]
        );
        $productRepository = $this->getMock(ObjectRepository::class);
        $productRepository->method('find')->willReturnMap(
            [
                [1, $product],
                [2, null],
            ]
        );

        $em = $this->getMock(ObjectManager::class);
        $em->method('getRepository')
            ->willReturnMap(
                [
                    [Scope::class, $scopeRepository],
                    [Product::class, $productRepository],
                    [ProductVisibility::class, $visibilityRepository],
                ]
            );
        $this->registry->method('getManagerForClass')->willReturn($em);
        $this->visibilityMessageFactory = new VisibilityMessageFactory($this->registry);
    }

    public function testCreateMessage()
    {
        $visibility = $this->getEntity(ProductVisibility::class, ['id' => 1]);
        $visibility->setScope($this->getEntity(Scope::class, ['id' => 5]))
            ->setProduct($this->getEntity(Product::class, ['id' => 6]));


        $this->assertEquals(
            [
                VisibilityMessageFactory::ID => 1,
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => 6,
                VisibilityMessageFactory::SCOPE_ID => 5,
            ],
            $this->visibilityMessageFactory->createMessage($visibility)
        );
    }

    public function testGetExistingEntityFromMessage()
    {
        $actual = $this->visibilityMessageFactory->getEntityFromMessage(
            [
                VisibilityMessageFactory::ID => 1,
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => 1,
                VisibilityMessageFactory::SCOPE_ID => 1,
            ]
        );
        $expected = $this->getEntity(
            ProductVisibility::class,
            [
                'id' => 1,
                'visibility' => 'hidden',
                'product' => $this->getEntity(Product::class, ['id' => 1]),
                'scope' => $this->getEntity(Scope::class, ['id' => 1]),
            ]
        );
        $this->assertEquals($expected, $actual);
    }

    public function testGetEntityFromMessageNewWithDefaultVisibility()
    {
        $actual = $this->visibilityMessageFactory->getEntityFromMessage(
            [
                VisibilityMessageFactory::ID => 2,
                VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                VisibilityMessageFactory::TARGET_ID => 1,
                VisibilityMessageFactory::SCOPE_ID => 1,
            ]
        );
        $expected = $this->getEntity(
            ProductVisibility::class,
            [
                'id' => null,
                'visibility' => ProductVisibility::CATEGORY,
                'product' => $this->getEntity(Product::class, ['id' => 1]),
                'scope' => $this->getEntity(Scope::class, ['id' => 1]),
            ]
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider getInvalidMessageDataProvider
     *
     * @param array $data
     * @param string $message
     */
    public function testGetEntityFromMessageException(array $data, $message)
    {
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $this->visibilityMessageFactory->getEntityFromMessage($data);
    }

    /**
     * @return array
     */
    public function getInvalidMessageDataProvider()
    {
        return [
            'required_data_options_missing' => [
                'data' => [],
                'message' =>
                    'The required options "entity_class_name", "id", "scope_id", '
                    .'"target_class_name", "target_id" are missing.',
            ],
            'invalid_id_type' => [
                'data' => [
                    VisibilityMessageFactory::ID => 'string',
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                    VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                    VisibilityMessageFactory::TARGET_ID => 6,
                    VisibilityMessageFactory::SCOPE_ID => 5,
                ],
                'message' => 'The option "id" with value "string"'
                    .' is expected to be of type "int", but is of type "string".',
            ],
            'invalid_scope_id_type' => [
                'data' => [
                    VisibilityMessageFactory::ID => 1,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                    VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                    VisibilityMessageFactory::TARGET_ID => 6,
                    VisibilityMessageFactory::SCOPE_ID => 'string',
                ],
                'message' => 'The option "scope_id" with value "string"'
                    .' is expected to be of type "int", but is of type "string".',
            ],
            'invalid_target_id_type' => [
                'data' => [
                    VisibilityMessageFactory::ID => 1,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                    VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                    VisibilityMessageFactory::TARGET_ID => 'string',
                    VisibilityMessageFactory::SCOPE_ID => 5,
                ],
                'message' => 'The option "target_id" with value "string"'
                    .' is expected to be of type "int", but is of type "string".',
            ],
            'invalid_target_class_name_not_valid' => [
                'data' => [
                    VisibilityMessageFactory::ID => 1,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                    VisibilityMessageFactory::TARGET_CLASS_NAME => '\NotExistingClass',
                    VisibilityMessageFactory::TARGET_ID => 'string',
                    VisibilityMessageFactory::SCOPE_ID => 5,
                ],
                'message' => 'The option "target_class_name" with value "\NotExistingClass" is invalid.',
            ],
            'invalid_entity_class_name_not_visibility_interface' => [
                'data' => [
                    VisibilityMessageFactory::ID => 1,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => '\stdClass',
                    VisibilityMessageFactory::TARGET_CLASS_NAME => 1,
                    VisibilityMessageFactory::TARGET_ID => 'string',
                    VisibilityMessageFactory::SCOPE_ID => 5,
                ],
                'message' => 'The option "entity_class_name" with value "\stdClass" is invalid.',
            ],
            'target_not_found_by_id' => [
                'data' => [
                    VisibilityMessageFactory::ID => 2,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                    VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                    VisibilityMessageFactory::TARGET_ID => 2,
                    VisibilityMessageFactory::SCOPE_ID => 1,
                ],
                'message' => 'Target object was not found.',
            ],
            'scope_not_found_by_id' => [
                'data' => [
                    VisibilityMessageFactory::ID => 2,
                    VisibilityMessageFactory::ENTITY_CLASS_NAME => ProductVisibility::class,
                    VisibilityMessageFactory::TARGET_CLASS_NAME => Product::class,
                    VisibilityMessageFactory::TARGET_ID => 1,
                    VisibilityMessageFactory::SCOPE_ID => 2
                ],
                'message' => 'Scope object was not found.',
            ],
        ];
    }
}
