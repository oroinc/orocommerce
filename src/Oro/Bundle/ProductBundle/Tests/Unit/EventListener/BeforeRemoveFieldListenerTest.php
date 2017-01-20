<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Event\BeforeRemoveFieldEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\BeforeRemoveFieldListener;

class BeforeRemoveFieldListenerTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * @return array
     */
    public function onBeforeRemoveFieldDataProvider()
    {
        return [
            'Class unsupported' => [
                'className' => \stdClass::class,
                'fieldName' => '',
                'expected' => [
                    'hasErrors' => false,
                    'validationMessage' => '',
                ],
            ],
            'Class supported, field is used' => [
                'className' => Product::class,
                'fieldName' => 'color',
                'expected' => [
                    'hasErrors' => true,
                    'validationMessage' => 'Cannot remove field because it\'s used as a variant field in the following configurable products: CNFPRD1, CNFPRD3',
                ],
            ],
            'Class supported, field is not used' => [
                'className' => Product::class,
                'fieldName' => 'unused_field',
                'expected' => [
                    'hasErrors' => false,
                    'validationMessage' => '',
                ],
            ],
        ];
    }
    // @codingStandardsIgnoreEnd

    /**
     * @return Product[]
     */
    private function getConfigurableProducts()
    {
        $product1 = new Product();
        $product1->setSku('CNFPRD1');
        $product1->setType(Product::TYPE_CONFIGURABLE);
        $product1->setVariantFields([
            'size',
            'color',
            'new_collection',
        ]);

        $product2 = new Product();
        $product2->setSku('CNFPRD2');
        $product2->setType(Product::TYPE_CONFIGURABLE);
        $product2->setVariantFields([
            'size',
            'new_collection',
        ]);

        $product3 = new Product();
        $product3->setSku('CNFPRD3');
        $product3->setType(Product::TYPE_CONFIGURABLE);
        $product3->setVariantFields([
            'size',
            'color',
            'new_collection',
        ]);

        return [
            $product1,
            $product2,
            $product3,
        ];
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array $expected
     * @dataProvider onBeforeRemoveFieldDataProvider
     */
    public function testOnBeforeRemoveField($className, $fieldName, array $expected)
    {
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new BeforeRemoveFieldListener($doctrineHelper);

        $event = new BeforeRemoveFieldEvent($className, $fieldName);
        $this->assertFalse($event->hasErrors());
        $this->assertEquals('', $event->getValidationMessage());

        $entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($entityRepository);

        $entityRepository->expects($this->any())
            ->method('findBy')
            ->with([
                'type' => Product::TYPE_CONFIGURABLE
            ])
            ->willReturn($this->getConfigurableProducts());

        $listener->onBeforeRemoveField($event);

        $this->assertEquals($expected['hasErrors'], $event->hasErrors());
        $this->assertEquals($expected['validationMessage'], $event->getValidationMessage());
    }
}
