<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\FilterCollection;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\AccountBundle\Doctrine\DoctrineFiltersListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\AccountBundle\Doctrine\SoftDeleteableFilter;

class DoctrineFiltersListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider onRequestDataProvider
     *
     * @param bool $isFrontEnd
     */
    public function testOnRequest($isFrontEnd)
    {
        $registry = $this->getRegistryMock();

        $frontendHelper = $this->getFrontendHelperMock();
        $frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn($isFrontEnd);

        if ($isFrontEnd) {
            $em = $this->getEmMock();
            $filterCollection = $this->getFilterCollectionMock();
            $filterCollection->expects($this->once())
                ->method('enable')
                ->willReturn(new SoftDeleteableFilter($em));

            $em->expects($this->once())
                ->method('getFilters')
                ->willReturn($filterCollection);

            $registry->expects($this->once())
                ->method('getManager')
                ->willReturn($em);
        }

        $listener = new DoctrineFiltersListener($registry, $frontendHelper);
        $listener->onRequest();
    }

    /**
     * @return array
     */
    public function onRequestDataProvider()
    {
        return [
            'frontend request' => [
                'isFrontEnd' => true,
            ],
            'backend request' => [
                'isFrontEnd' => false,
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function getRegistryMock()
    {
        return $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEmMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FrontendHelper
     */
    protected function getFrontendHelperMock()
    {
        return $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterCollection
     */
    protected function getFilterCollectionMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\Query\FilterCollection')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
