<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteScopedTypeMockProvider extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @return WebsiteScopedDataType
     */
    public function getWebsiteScopedDataType()
    {
        $websites = [1 => $this->getEntity(Website::class, ['id' => 1])];
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getReference')
            ->with(Website::class, 1)
            ->willReturn($this->getEntity(Website::class, ['id' => 1]));

        $websiteQB = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMock();
        $websiteQB
            ->expects($this->any())
            ->method('getResult')
            ->willReturn($websites);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->with('website')
            ->willReturn($websiteQB);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry*/
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $aclHelper = $this->createMock(AclHelper::class);
        $aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($websiteQB);

        return new WebsiteScopedDataType($registry, $aclHelper);
    }
}
