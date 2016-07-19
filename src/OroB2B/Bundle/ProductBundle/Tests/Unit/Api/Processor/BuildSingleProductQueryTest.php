<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;

use OroB2B\Bundle\ProductBundle\Api\Processor\BuildSingleProductQuery;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class BuildSingleProductQueryTest extends GetProcessorOrmRelatedTestCase
{
    /** @var UpdateContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $criteriaConnector;

    /** @var BuildSingleProductQuery */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context = new UpdateContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras(
            [
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FiltersConfigExtra()
            ]
        );

        $this->criteriaConnector = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\CriteriaConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildSingleProductQuery($this->doctrineHelper, $this->criteriaConnector);
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProductNotExistsInRequest()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }
    
    public function testProcessBuildsQuery()
    {
        $this->context->setRequestData(['sku' => 'product.1']);

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setCriteria($criteria);
        $this->context->setClassName(Product::class);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());
        $this->assertEquals(
            $this->context->getQuery()->getDql(),
            sprintf('SELECT e FROM %s e WHERE e.sku = :sku', Product::class)
        );
    }
}
