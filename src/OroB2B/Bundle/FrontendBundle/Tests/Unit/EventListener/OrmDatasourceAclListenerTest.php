<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;

use OroB2B\Bundle\FrontendBundle\EventListener\OrmDatasourceAclListener;

class OrmDatasourceAclListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * @var OrmDatasourceAclListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrmResultBefore
     */
    protected $event;

    protected function setUp()
    {
        $this->metadataProvider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface');

        $this->listener = new OrmDatasourceAclListener($this->metadataProvider);

        $this->event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultBefore')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->metadataProvider, $this->listener, $this->event);
    }

    /**
     * @dataProvider onResultBeforeDataProvider
     *
     * @param array $entities
     * @param bool $expectedSkipAclCheck
     */
    public function testOnResultBefore($entities = [], $expectedSkipAclCheck = true)
    {
        /** @var FromClause $from */
        $from = $this->getMockBuilder('Doctrine\ORM\Query\AST\FromClause')->disableOriginalConstructor()->getMock();

        foreach ($entities as $className => $hasOwner) {
            $from->identificationVariableDeclarations[] = $this->createIdentVariableDeclarationMock($className);
        }

        $this->event->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->createQueryMock($from));

        $this->metadataProvider->expects($this->atLeastOnce())
            ->method('getMetadata')
            ->willReturnCallback(
                function ($className) use ($entities) {
                    return $this->createOwnershipMetadataMock($entities[$className]);
                }
            );

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->createDatagridMock($expectedSkipAclCheck));

        $this->listener->onResultBefore($this->event);
    }

    /**
     * @return array
     */
    public function onResultBeforeDataProvider()
    {
        return [
            [
                'entities' => [
                    '\stdClass' . mt_rand() => true,
                    '\stdClass' . mt_rand() => true
                ],
                'expectedSkipAclCheck' => false
            ],
            [
                'entities' => [
                    '\stdClass' . mt_rand() => true,
                    '\stdClass' . mt_rand() => false
                ],
                'expectedSkipAclCheck' => false
            ],
            [
                'entities' => [
                    '\stdClass' . mt_rand() => false,
                    '\stdClass' . mt_rand() => true
                ],
                'expectedSkipAclCheck' => false
            ],
            [
                'entities' => [
                    '\stdClass' . mt_rand() => false,
                    '\stdClass' . mt_rand() => false
                ],
                'expectedSkipAclCheck' => true
            ],
            [
                'entities' => [
                    '\stdClass' . mt_rand() => false
                ],
                'expectedSkipAclCheck' => true
            ],
            [
                'entities' => [
                    '\stdClass' . mt_rand() => true
                ],
                'expectedSkipAclCheck' => false
            ]
        ];
    }

    /**
     * @param bool $expectedSkipAclCheck
     * @return DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createDatagridMock($expectedSkipAclCheck)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration $datagridConfiguration */
        $datagridConfiguration = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfiguration->expects($expectedSkipAclCheck ? $this->once() : $this->never())
            ->method('offsetSetByPath')
            ->with(Builder::DATASOURCE_SKIP_ACL_CHECK, true)
            ->willReturnSelf();

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        return $datagrid;
    }

    /**
     * @param FromClause $from
     * @return AbstractQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createQueryMock(FromClause $from)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|SelectStatement $select */
        $select = $this->getMockBuilder('Doctrine\ORM\Query\AST\SelectStatement')
            ->disableOriginalConstructor()
            ->getMock();
        $select->fromClause = $from;

        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractQuery $query */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['getAST'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getAST')
            ->willReturn($select);

        return $query;
    }

    /**
     * @param string $className
     * @return IdentificationVariableDeclaration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createIdentVariableDeclarationMock($className)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|RangeVariableDeclaration $rangeVariableDeclaration */
        $rangeVariableDeclaration = $this->getMockBuilder('Doctrine\ORM\Query\AST\RangeVariableDeclaration')
            ->disableOriginalConstructor()
            ->getMock();
        $rangeVariableDeclaration->abstractSchemaName = $className;

        /** @var \PHPUnit_Framework_MockObject_MockObject|IdentificationVariableDeclaration $identVariableDeclaration */
        $identVariableDeclaration = $this->getMockBuilder('Doctrine\ORM\Query\AST\IdentificationVariableDeclaration')
            ->disableOriginalConstructor()
            ->getMock();
        $identVariableDeclaration->rangeVariableDeclaration = $rangeVariableDeclaration;

        return $identVariableDeclaration;
    }

    /**
     * @param bool $hasOwner
     * @return OwnershipMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOwnershipMetadataMock($hasOwner)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OwnershipMetadataInterface $metadata */
        $metadata = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface');
        $metadata->expects($this->once())
            ->method('hasOwner')
            ->willReturn($hasOwner);

        return $metadata;
    }
}
