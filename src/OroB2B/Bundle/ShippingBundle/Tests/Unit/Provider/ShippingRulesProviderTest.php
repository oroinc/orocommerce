<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingRulesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodRegistry;

    /**
     * @var ShippingRulesProvider
     */
    protected $shippingRulesProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->shippingMethodRegistry = $this->getMock(ShippingMethodRegistry::class);
        $this->shippingRulesProvider = new ShippingRulesProvider($this->doctrineHelper, $this->shippingMethodRegistry);

        $shippingRule = $this->getEntity(
            ShippingRule::class,
            ['id' => 1, 'name' => 'ShippingRule.1', 'priority' => 1, 'conditions' => '1>3']
        );

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->any())
            ->method('findAll')
            ->willReturn([$shippingRule])
        ;

        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BShippingBundle:ShippingRule')
            ->willReturn($repository)
        ;

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with('OroB2BShippingBundle:ShippingRule')
            ->willReturn($entityManager)
        ;
    }

    public function testGetShippingRules()
    {
        $shippingRules = $this->shippingRulesProvider->getShippingRules();

        $this->assertEquals(1, count($shippingRules));
        $this->assertEquals(1, $shippingRules[0]->getId());
    }

    public function testGetApplicableShippingRules()
    {
        $shippingContext = $this->getMock(ShippingContextAwareInterface::class);

        $shippingContext->expects($this->any())
            ->method('getShippingContext')
            ->willReturn([])
        ;

        $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);

        $this->assertEquals(1, count($rules));
        $this->assertEquals(1, $rules[0]->getId());
    }
}
