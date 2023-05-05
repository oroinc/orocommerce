<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\ReflectionUtil;

class WebsiteScopedTypeMockProvider extends \PHPUnit\Framework\TestCase
{
    public function getWebsiteScopedDataType(): WebsiteScopedDataType
    {
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getReference')
            ->with(Website::class, 1)
            ->willReturn($this->getWebsite(1));

        $websiteQuery = $this->createMock(AbstractQuery::class);
        $websiteQuery->expects($this->any())
            ->method('getResult')
            ->willReturn([1 => $this->getWebsite(1)]);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->with('website')
            ->willReturn($this->createMock(QueryBuilder::class));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $aclHelper = $this->createMock(AclHelper::class);
        $aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($websiteQuery);

        return new WebsiteScopedDataType($doctrine, $aclHelper);
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }
}
