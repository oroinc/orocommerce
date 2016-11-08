<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountSelectType;

class FrontendAccountSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendAccountSelectType
     */
    protected $formType;

    /** @var AclHelper $aclHelper */
    protected $aclHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->aclHelper = $this->createAclHelperMock();
        $this->formType = new FrontendAccountSelectType($this->aclHelper);
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendAccountSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_translatable_entity', $this->formType->getParent());
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
        $this->assertArrayHasKey('class', $defaults);
        $this->assertArrayHasKey('query_builder', $defaults);

        /** @var \Closure $callback */
        $callback = $defaults['query_builder'];
        $this->assertInstanceOf('\Closure', $callback);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getAccountsQueryBuilder')
            ->with($this->aclHelper)
            ->willReturn($queryBuilder);

        /** @var \Closure $queryBuilderCallback */
        $queryBuilderCallback = $callback($repository);
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $queryBuilderCallback);

        $this->assertEquals($queryBuilder, $callback($repository));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAclHelperMock()
    {
        return $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
