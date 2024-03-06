<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShippingMethodLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var ShippingMethodOrganizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationProvider;

    /** @var ShippingMethodLabelFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->organizationProvider = $this->createMock(ShippingMethodOrganizationProvider::class);

        $this->formatter = new ShippingMethodLabelFormatter(
            $this->shippingMethodProvider,
            $this->organizationProvider
        );
    }

    private function getShippingMethodType(string $label): ShippingMethodTypeInterface
    {
        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type->expects(self::once())
            ->method('getLabel')
            ->willReturn($label);

        return $type;
    }

    public function testFormatShippingMethodLabel(): void
    {
        $shippingMethodName = 'shipping_method';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::never())
            ->method('getLabel');
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(false);

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertSame('', $this->formatter->formatShippingMethodLabel($shippingMethodName));
    }

    public function testFormatShippingMethodLabelWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::never())
            ->method('getLabel');
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(false);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertSame('', $this->formatter->formatShippingMethodLabel($shippingMethodName, $organization));
    }

    public function testFormatShippingMethodLabelForGroupedShippingMethod(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingMethodLabel = 'Shipping Method Label';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodLabel);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodLabel,
            $this->formatter->formatShippingMethodLabel($shippingMethodName)
        );
    }

    public function testFormatShippingMethodLabelForGroupedShippingMethodWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';
        $shippingMethodLabel = 'Shipping Method Label';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodLabel);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodLabel,
            $this->formatter->formatShippingMethodLabel($shippingMethodName, $organization)
        );
    }

    public function testFormatShippingMethodLabelWithShippingMethodNameIsNull(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodLabel(null)
        );
    }

    public function testFormatShippingMethodLabelWithShippingMethodNameIsNullWithOrganization(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodLabel(null, $this->createMock(Organization::class))
        );
    }

    public function testFormatShippingMethodTypeLabel(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';
        $shippingMethodTypeLabel = 'Shipping Method Type Label';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($this->getShippingMethodType($shippingMethodTypeLabel));

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodTypeLabel,
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodTypeLabelWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';
        $shippingMethodTypeLabel = 'Shipping Method Type Label';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($this->getShippingMethodType($shippingMethodTypeLabel));

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodTypeLabel,
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName, $organization)
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodDoesNotExist(): void
    {
        $shippingMethodName = 'shipping_method';

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn(null);

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, 'shipping_type')
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodDoesNotExistWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn(null);

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, 'shipping_type', $organization)
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodTypeDoesNotExist(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn(null);

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodTypeDoesNotExistWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn(null);

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName, $organization)
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodNameIsNull(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel(null, 'shipping_type')
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodNameIsNullWithOrganization(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel(
                null,
                'shipping_type',
                $this->createMock(Organization::class)
            )
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingTypeNameIsNull(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel('shipping_method', null)
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingTypeNameIsNullWithOrganization(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodTypeLabel(
                'shipping_method',
                null,
                $this->createMock(Organization::class)
            )
        );
    }

    public function testFormatShippingMethodWithTypeLabel(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodName);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($this->getShippingMethodType($shippingTypeName));

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodName . ', ' . $shippingTypeName,
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodName);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($this->getShippingMethodType($shippingTypeName));

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodName . ', ' . $shippingTypeName,
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName, $organization)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWithEmptyMethod(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::never())
            ->method('getLabel');
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(false);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($this->getShippingMethodType($shippingTypeName));

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingTypeName,
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWithEmptyMethodWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::never())
            ->method('getLabel');
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(false);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($this->getShippingMethodType($shippingTypeName));

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingTypeName,
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName, $organization)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWhenShippingMethodNameIsNull(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodWithTypeLabel(null, 'shipping_type')
        );
    }

    public function testFormatShippingMethodWithTypeLabelWhenShippingMethodNameIsNullWithOrganization(): void
    {
        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame(
            '',
            $this->formatter->formatShippingMethodWithTypeLabel(
                null,
                'shipping_type',
                $this->createMock(Organization::class)
            )
        );
    }

    public function testFormatShippingMethodWithTypeLabelWhenShippingTypeNameIsNull(): void
    {
        $shippingMethodName = 'shipping_method';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodName);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);
        $shippingMethod->expects(self::never())
            ->method('getType');

        $this->organizationProvider->expects(self::never())
            ->method(self::anything());

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodName . ', ',
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, null)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWhenShippingTypeNameIsNullWithOrganization(): void
    {
        $previousOrganization = $this->createMock(Organization::class);
        $organization = $this->createMock(Organization::class);
        $shippingMethodName = 'shipping_method';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodName);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);
        $shippingMethod->expects(self::never())
            ->method('getType');

        $this->organizationProvider->expects(self::once())
            ->method('getOrganization')
            ->willReturn($previousOrganization);
        $this->organizationProvider->expects(self::exactly(2))
            ->method('setOrganization')
            ->withConsecutive([$organization], [$previousOrganization]);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodName . ', ',
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, null, $organization)
        );
    }
}
