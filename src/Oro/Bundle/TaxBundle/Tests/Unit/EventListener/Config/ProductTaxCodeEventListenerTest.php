<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\Config;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\EventListener\Config\ProductTaxCodeEventListener;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductTaxCodeEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ProductTaxCodeEventListener $listener;
    private ConfigManager $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject  */
    private $aclHelper;

    private array $data = [];

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->listener = new ProductTaxCodeEventListener(
            $this->doctrineHelper,
            $this->aclHelper,
            'digital_products_eu'
        );

        $this->data = ['CODE1', null, 1, new \stdClass(), '', 'CODE2', '2'];
    }

    public function testFormPreSetWithoutKey()
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, []);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->listener->formPreSet($event);
        $this->assertEquals([], $event->getSettings());
    }

    public function testFormPreSet()
    {
        $settings = ['oro_tax___digital_products_eu' => ['value' => $this->data]];
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $expr = $this->createMock(Expr::class);
        $expr->expects(self::once())
            ->method('in')
            ->with('taxCode.code', ':codes')
            ->willReturn($this->createMock(Expr\Func::class));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('codes', $this->data)
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('where')
            ->willReturn($qb);

        $taxCodes = [
            $this->getEntity(ProductTaxCode::class, ['code' => 'CODE1']),
            $this->getEntity(ProductTaxCode::class, ['code' => 'CODE2']),
        ];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($taxCodes);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(ProductTaxCode::class, 'taxCode')
            ->willReturn($qb);

        $this->listener->formPreSet($event);

        $this->assertEquals(['oro_tax___digital_products_eu' => ['value' => $taxCodes]], $event->getSettings());
    }

    public function testBeforeSaveWithoutValueKey()
    {
        $settings = [];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->beforeSave($event);

        $this->assertEquals($settings, $event->getSettings());
    }

    public function testBeforeSave()
    {
        $settings = [
            'value' => $this->data
        ];

        $taxCodes = [
            $this->getEntity(ProductTaxCode::class, ['id' => 1, 'code' => 'CODE1']),
            $this->getEntity(ProductTaxCode::class, ['id' => 2, 'code' => 'CODE2']),
        ];

        $repository = $this->createMock(AbstractTaxCodeRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn($taxCodes);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ProductTaxCode::class)
            ->willReturn($repository);

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->beforeSave($event);

        $this->assertEquals(['value' => ['CODE1', 'CODE2']], $event->getSettings());
    }
}
