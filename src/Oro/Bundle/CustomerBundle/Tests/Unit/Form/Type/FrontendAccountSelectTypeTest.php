<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->aclHelper = $this->createAclHelperMock();
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->formType = new FrontendAccountSelectType($this->aclHelper, $this->registry);
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

        $criteria = new Criteria();
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $accountUserRepository =
            $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()
                ->getMock();

        $accountUserRepository
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->with('account')
            ->willReturn($queryBuilder);

        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroCustomerBundle:Account')
            ->willReturn($accountUserRepository);

        $this->aclHelper
            ->expects($this->any())
            ->method('applyAclToCriteria')
            ->with(AccountUser::class, $criteria, 'VIEW', ['account' => 'account.id'])
            ->willReturn($queryBuilder);

        $queryBuilder
            ->expects($this->any())
            ->method('addCriteria')
            ->with($criteria);

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
