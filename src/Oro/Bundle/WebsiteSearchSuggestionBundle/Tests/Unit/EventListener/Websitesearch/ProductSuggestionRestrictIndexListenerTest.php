<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener\Websitesearch;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearch\ProductSuggestionRestrictIndexListener;
use PHPUnit\Framework\MockObject\MockObject;

final class ProductSuggestionRestrictIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    private ProductSuggestionRestrictIndexListener $listener;

    private WebsiteContextManager&MockObject $websiteContextManager;

    private OrganizationRestrictionProviderInterface&MockObject $organizationRestrictionProvider;

    private AbstractWebsiteLocalizationProvider&MockObject $websiteLocalizationProvider;

    private SuggestionRepository $productSuggestionRepository;

    private RestrictIndexEntityEvent&MockObject $event;

    private QueryBuilder $queryBuilder;

    private ManagerRegistry&MockObject $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->productSuggestionRepository = $this->createMock(SuggestionRepository::class);

        $this->listener = new ProductSuggestionRestrictIndexListener(
            $this->websiteContextManager = $this->createMock(WebsiteContextManager::class),
            $this->organizationRestrictionProvider = $this->createMock(
                OrganizationRestrictionProviderInterface::class
            ),
            $this->websiteLocalizationProvider = $this->createMock(
                AbstractWebsiteLocalizationProvider::class
            ),
            $this->registry = $this->createMock(ManagerRegistry::class)
        );

        $this->event = $this->createMock(RestrictIndexEntityEvent::class);

        $this->event
            ->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder = $this->createMock(QueryBuilder::class));
    }

    public function testThatNotProductSuggestionEntitiesSkipped(): void
    {
        $this->queryBuilder
            ->expects(self::once())
            ->method('getRootEntities')
            ->willReturn(['test']);

        $this->websiteContextManager
            ->expects(self::never())
            ->method('getWebsite');

        $this->event
            ->expects(self::never())
            ->method('getContext');

        $this->listener->onRestrictIndexEntityEvent($this->event);
    }

    public function testThatListenerWithoutWebsiteSkipped(): void
    {
        $this->queryBuilder
            ->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([Suggestion::class]);

        $this->event
            ->expects(self::once())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsite');

        $this->websiteLocalizationProvider
            ->expects($this->never())
            ->method('getLocalizationsByWebsiteId');

        $this->listener->onRestrictIndexEntityEvent($this->event);
    }

    public function testThatListenerApplyLocalisationRestrictions(): void
    {
        $this->registry
            ->expects(self::once())
            ->method('getRepository')
            ->with(Suggestion::class)
            ->willReturn($this->productSuggestionRepository);

        $this->queryBuilder
            ->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([Suggestion::class]);

        $this->event
            ->expects(self::once())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsite')
            ->willReturn($website = $this->createMock(Website::class));

        $website
            ->expects(self::once())
            ->method('getId')
            ->willReturn($websiteId = 2);

        $this->websiteLocalizationProvider
            ->expects(self::once())
            ->method('getLocalizationsByWebsiteId')
            ->with($websiteId)
            ->willReturn([$localisation = $this->createMock(Localization::class)]);

        $this->productSuggestionRepository
            ->expects(self::once())
            ->method('applyLocalizationRestrictions')
            ->with($this->queryBuilder, [$localisation]);

        $website
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization = $this->createMock(Organization::class));

        $this->listener->onRestrictIndexEntityEvent($this->event);
    }

    public function testThatListenerApplyOrganizationRestrictions(): void
    {
        $this->queryBuilder
            ->expects(self::once())
            ->method('getRootEntities')
            ->willReturn([Suggestion::class]);

        $this->event
            ->expects(self::once())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsite')
            ->willReturn($website = $this->createMock(Website::class));

        $website
            ->expects(self::once())
            ->method('getId')
            ->willReturn($websiteId = 2);

        $this->websiteLocalizationProvider
            ->expects(self::once())
            ->method('getLocalizationsByWebsiteId')
            ->with($websiteId)
            ->willReturn([]);

        $this->productSuggestionRepository
            ->expects($this->never())
            ->method('applyLocalizationRestrictions');

        $website
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization = $this->createMock(Organization::class));

        $this->organizationRestrictionProvider
            ->expects(self::once())
            ->method('applyOrganizationRestrictions')
            ->with($this->queryBuilder, $organization);

        $this->listener->onRestrictIndexEntityEvent($this->event);
    }
}
