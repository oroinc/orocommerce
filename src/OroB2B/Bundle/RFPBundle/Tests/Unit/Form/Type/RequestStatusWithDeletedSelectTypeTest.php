<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusWithDeletedSelectType;

class RequestStatusWithDeletedSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus';

    /**
     * @var RequestStatusWithDeletedSelectType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $choices = [
        1 => 'Opened',
        2 => 'Closed'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new RequestStatusWithDeletedSelectType();
        $this->formType->setEntityClass(self::ENTITY_CLASS);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback([$this, 'assertDefaults']);

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * @param array $defaults
     */
    public function assertDefaults(array $defaults)
    {
        $this->assertArrayHasKey('query_builder', $defaults);

        /** @var \Closure $callback */
        $callback = $defaults['query_builder'];
        $this->assertInstanceOf('\Closure', $callback);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getNotDeletedAndDeletedWithRequestsStatusesQueryBuilder')
            ->willReturn($queryBuilder);

        /** @var \Closure $queryBuilderCallback */
        $queryBuilderCallback = $callback($repository);
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $queryBuilderCallback);

        $this->assertEquals($queryBuilder, $callback($repository));
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestStatusWithDeletedSelectType::NAME, $this->formType->getName());
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('translatable_entity', $this->formType->getParent());
    }
}
