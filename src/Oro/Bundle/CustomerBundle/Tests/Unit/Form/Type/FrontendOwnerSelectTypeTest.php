<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendOwnerSelectType;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendOwnerSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendOwnerSelectType
     */
    protected $formType;

    /** @var AclHelper $aclHelper */
    protected $aclHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->aclHelper = $this->createAclHelperMock();
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $this->formType = new FrontendOwnerSelectType($this->aclHelper, $this->registry, $this->configProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendOwnerSelectType::NAME, $this->formType->getName());
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

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->any())
            ->method('get')
            ->will($this->returnValue('FRONTEND_CUSTOMER'));

        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $criteria = new Criteria();
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $customerUserRepository =
            $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()
                ->getMock();

        $customerUserRepository
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->with('customer')
            ->willReturn($queryBuilder);

        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroCustomerBundle:Customer')
            ->willReturn($customerUserRepository);

        $this->aclHelper
            ->expects($this->any())
            ->method('applyAclToCriteria')
            ->with(CustomerUser::class, $criteria, 'VIEW', ['customer' => 'customer.id'])
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
