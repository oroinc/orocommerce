<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Model;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PromotionAwareEntityHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var PromotionAwareEntityHelper */
    private $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->helper = new PromotionAwareEntityHelper($this->configManager);
    }

    private function getEntityConfig(string $entityClass, array $values): Config
    {
        return new Config(new EntityConfigId('promotion', $entityClass), $values);
    }

    public function testIsCouponAwareForNotConfiguredEntityClass(): void
    {
        $entityClass = \stdClass::class;

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->helper->isCouponAware($entityClass));
    }

    public function testIsCouponAwareForNotConfiguredEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->helper->isCouponAware($entity));
    }

    public function testIsCouponAwareForNotCouponAwareEntityClass(): void
    {
        $entityClass = \stdClass::class;
        $entityConfig = $this->getEntityConfig($entityClass, []);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isCouponAware($entityClass));
    }

    public function testIsCouponAwareForNotCouponAwareEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);
        $entityConfig = $this->getEntityConfig($entityClass, []);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isCouponAware($entity));
    }

    public function testIsCouponAwareForDisabledCouponAwareEntityClass(): void
    {
        $entityClass = \stdClass::class;
        $entityConfig = $this->getEntityConfig($entityClass, ['is_coupon_aware' => false]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isCouponAware($entityClass));
    }

    public function testIsCouponAwareForDisabledCouponAwareEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);
        $entityConfig = $this->getEntityConfig($entityClass, ['is_coupon_aware' => false]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isCouponAware($entity));
    }

    public function testIsCouponAwareForCouponAwareEntityClass(): void
    {
        $entityClass = \stdClass::class;
        $entityConfig = $this->getEntityConfig($entityClass, ['is_coupon_aware' => true]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertTrue($this->helper->isCouponAware($entityClass));
    }

    public function testIsCouponAwareForCouponAwareEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);
        $entityConfig = $this->getEntityConfig($entityClass, ['is_coupon_aware' => true]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertTrue($this->helper->isCouponAware($entity));
    }

    public function testIsPromotionAwareForNotConfiguredEntityClass(): void
    {
        $entityClass = \stdClass::class;

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->helper->isPromotionAware($entityClass));
    }

    public function testIsPromotionAwareForNotConfiguredEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->helper->isPromotionAware($entity));
    }

    public function testIsPromotionAwareForNotPromotionAwareEntityClass(): void
    {
        $entityClass = \stdClass::class;
        $entityConfig = $this->getEntityConfig($entityClass, []);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isPromotionAware($entityClass));
    }

    public function testIsPromotionAwareForNotPromotionAwareEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);
        $entityConfig = $this->getEntityConfig($entityClass, []);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isPromotionAware($entity));
    }

    public function testIsPromotionAwareForDisabledPromotionAwareEntityClass(): void
    {
        $entityClass = \stdClass::class;
        $entityConfig = $this->getEntityConfig($entityClass, ['is_promotion_aware' => false]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isPromotionAware($entityClass));
    }

    public function testIsPromotionAwareForDisabledPromotionAwareEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);
        $entityConfig = $this->getEntityConfig($entityClass, ['is_promotion_aware' => false]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertFalse($this->helper->isPromotionAware($entity));
    }

    public function testIsPromotionAwareForPromotionAwareEntityClass(): void
    {
        $entityClass = \stdClass::class;
        $entityConfig = $this->getEntityConfig($entityClass, ['is_promotion_aware' => true]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertTrue($this->helper->isPromotionAware($entityClass));
    }

    public function testIsPromotionAwareForPromotionAwareEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = get_class($entity);
        $entityConfig = $this->getEntityConfig($entityClass, ['is_promotion_aware' => true]);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('promotion', $entityClass)
            ->willReturn($entityConfig);

        self::assertTrue($this->helper->isPromotionAware($entity));
    }
}
