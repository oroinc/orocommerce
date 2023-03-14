<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\MethodConfigsDatagridDataProvider;
use Twig\Environment;

class MethodConfigsDatagridDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var MethodConfigsDatagridDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);
        $this->twig = $this->createMock(Environment::class);

        $this->provider = new MethodConfigsDatagridDataProvider($this->organizationProvider, $this->twig);
    }

    public function testGetMethodsConfigs(): void
    {
        $organization = new Organization();
        $previousOrganization = new Organization();

        $record = new ResultRecord(['organization' => $organization]);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([self::identicalTo($organization)], [self::identicalTo($previousOrganization)]);

        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                '@OroShipping/ShippingMethodsConfigsRule/Datagrid/configurations.html.twig',
                ['record' => $record]
            )
            ->willReturn('_rendered_template_');

        self::assertEquals('_rendered_template_', $this->provider->getMethodsConfigs($record));
    }

    public function testGetMethodsConfigsWithExceptionDuringRenderingShouldRestorePreviousOrganization(): void
    {
        $this->expectException(\Exception::class);

        $organization = new Organization();
        $previousOrganization = new Organization();

        $record = new ResultRecord(['organization' => $organization]);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([self::identicalTo($organization)], [self::identicalTo($previousOrganization)]);

        $this->twig->expects(self::once())
            ->method('render')
            ->willThrowException(new \Exception());

        $this->provider->getMethodsConfigs($record);
    }
}
