<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Twig\OrderShippingExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OrderShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ShippingMethodLabelTranslator|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodLabelTranslator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var OrderShippingExtension */
    private $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingMethodLabelTranslator = $this->createMock(ShippingMethodLabelTranslator::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $container = self::getContainerBuilder()
            ->add('oro_shipping.translator.shipping_method_label', $this->shippingMethodLabelTranslator)
            ->add(DoctrineHelper::class, $this->doctrineHelper)
            ->getContainer($this);

        $this->extension = new OrderShippingExtension($container);
    }

    public function testGetShippingMethodLabelWhenNoShippingMethod(): void
    {
        $shippingMethod = null;
        $shippingMethodType = null;
        $shippingMethodLabel = 'label';

        $this->shippingMethodLabelTranslator->expects(self::once())
            ->method('getShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::isNull())
            ->willReturn($shippingMethodLabel);

        self::assertSame(
            $shippingMethodLabel,
            self::callTwigFunction(
                $this->extension,
                'oro_order_shipping_method_label',
                [$shippingMethod, $shippingMethodType]
            )
        );
    }

    public function testGetShippingMethodLabelWithOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $shippingMethod = 'method';
        $shippingMethodType = 'type';
        $shippingMethodLabel = 'label';

        $this->shippingMethodLabelTranslator->expects(self::once())
            ->method('getShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::identicalTo($organization))
            ->willReturn($shippingMethodLabel);

        self::assertSame(
            $shippingMethodLabel,
            self::callTwigFunction(
                $this->extension,
                'oro_order_shipping_method_label',
                [$shippingMethod, $shippingMethodType, $organization]
            )
        );
    }

    public function testGetShippingMethodLabelWithOrganizationId(): void
    {
        $organizationId = 123;
        $organization = $this->createMock(Organization::class);
        $shippingMethod = 'method';
        $shippingMethodType = 'type';
        $shippingMethodLabel = 'label';

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Organization::class, $organizationId)
            ->willReturn($organization);

        $this->shippingMethodLabelTranslator->expects(self::once())
            ->method('getShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType, self::identicalTo($organization))
            ->willReturn($shippingMethodLabel);

        self::assertSame(
            $shippingMethodLabel,
            self::callTwigFunction(
                $this->extension,
                'oro_order_shipping_method_label',
                [$shippingMethod, $shippingMethodType, $organizationId]
            )
        );
    }
}
