<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Owner;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Owner\EntityOwnershipDecisionMaker;

class EntityOwnershipDecisionMakerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OwnershipMetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OwnerTreeProvider
     */
    protected $treeProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityOwnershipDecisionMaker
     */
    protected $decisionMaker;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_security.ownership_tree_provider',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->treeProvider,
                        ],
                        [
                            'oro_security.owner.metadata_provider.chain',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->metadataProvider,
                        ],
                        [
                            'oro_security.security_facade',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->securityFacade,
                        ],
                    ]
                )
            );

        $this->decisionMaker = new EntityOwnershipDecisionMaker();
        $this->decisionMaker->setContainer($this->container);
    }

    protected function tearDown()
    {
        unset(
            $this->metadataProvider,
            $this->treeProvider,
            $this->decisionMaker,
            $this->container,
            $this->securityFacade
        );
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $user
     * @param bool $expectedResult
     */
    public function testSupports($user, $expectedResult)
    {
        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expectedResult, $this->decisionMaker->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'without security facade' => [
                'user' => null,
                'expectedResult' => false,
            ],
            'security facade with incorrect user class' => [
                'user' => new \stdClass(),
                'expectedResult' => false,
            ],
            'security facade with user class' => [
                'user' => new AccountUser(),
                'expectedResult' => true,
            ],
        ];
    }
}
