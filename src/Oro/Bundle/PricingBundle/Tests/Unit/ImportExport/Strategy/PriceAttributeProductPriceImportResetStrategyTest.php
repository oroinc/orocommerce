<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\PriceAttributeProductPriceImportResetStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PriceAttributeProductPriceImportResetStrategyTest extends TestCase
{
    const DELETED_COUNT = 5;

    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldHelper;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var PriceAttributeProductPriceImportResetStrategy
     */
    private $strategy;

    protected function setUp()
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->fieldHelper
            ->expects(static::any())
            ->method('getIdentityValues')
            ->willReturn(['value']);
        $this->fieldHelper
            ->expects(static::any())
            ->method('getFields')
            ->willReturn([]);

        $strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $strategyHelper
            ->expects(static::any())
            ->method('isGranted')
            ->willReturn(true);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->strategy = new PriceAttributeProductPriceImportResetStrategy(
            $this->createMock(EventDispatcherInterface::class),
            $strategyHelper,
            $this->fieldHelper,
            $this->createMock(DatabaseHelper::class),
            $this->createMock(ChainEntityClassNameProvider::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(NewEntitiesHelper::class),
            $this->doctrineHelper
        );
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(PriceAttributeProductPrice::class);
    }

    public function testProcessForNoPriceList()
    {
        $this->doctrineHelper
            ->expects(static::never())
            ->method('getEntityManager');

        $this->strategy->process(new PriceAttributeProductPrice());
    }

    public function testProcessForNewPriceList()
    {
        $this->doctrineHelper
            ->expects(static::never())
            ->method('getEntityManager');

        $entity = new PriceAttributeProductPrice();
        $entity->setPriceList(new PriceAttributePriceList());

        $this->strategy->process($entity);
    }

    public function testProcessResetsPricesOnlyOnce()
    {
        $repository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $repository
            ->expects(static::once())
            ->method('deletePricesByPriceList')
            ->willReturn(self::DELETED_COUNT);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->context
            ->expects(static::once())
            ->method('incrementDeleteCount')
            ->with(self::DELETED_COUNT);

        $priceList = $this->createMock(PriceAttributePriceList::class);
        $priceList
            ->expects(static::any())
            ->method('getId')
            ->willReturn(1);

        $entity = new PriceAttributeProductPrice();
        $entity->setPriceList($priceList);

        $this->strategy->process($entity);
        $this->strategy->process($entity);
    }
}
