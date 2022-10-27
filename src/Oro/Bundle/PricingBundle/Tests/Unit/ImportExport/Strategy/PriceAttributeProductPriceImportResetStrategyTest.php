<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Field\RelatedEntityStateHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\PriceAttributeProductPriceImportResetStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceAttributeProductPriceImportResetStrategyTest extends TestCase
{
    private const DELETED_COUNT = 5;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context;

    private DatabaseHelper|\PHPUnit\Framework\MockObject\MockObject $databaseHelper;

    private PriceAttributeProductPriceImportResetStrategy $strategy;

    protected function setUp(): void
    {
        $fieldHelper = $this->createMock(FieldHelper::class);
        $fieldHelper
            ->expects(static::any())
            ->method('getIdentityValues')
            ->willReturn(['value']);
        $fieldHelper
            ->expects(static::any())
            ->method('getEntityFields')
            ->willReturn([]);

        $strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $strategyHelper
            ->expects(static::any())
            ->method('isGranted')
            ->willReturn(true);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->databaseHelper = $this->createMock(DatabaseHelper::class);

        $this->strategy = new PriceAttributeProductPriceImportResetStrategy(
            $this->createMock(EventDispatcherInterface::class),
            $strategyHelper,
            $fieldHelper,
            $this->databaseHelper,
            $this->createMock(EntityClassNameProviderInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(NewEntitiesHelper::class),
            $this->doctrineHelper,
            $this->createMock(RelatedEntityStateHelper::class)
        );
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(PriceAttributeProductPrice::class);
    }

    public function testProcessForNoPriceList(): void
    {
        $this->strategy->process(new PriceAttributeProductPrice());
    }

    public function testProcessForNewPriceList(): void
    {
        $entity = new PriceAttributeProductPrice();
        $entity->setPriceList(new PriceAttributePriceList());

        $this->strategy->process($entity);
    }

    public function testProcessResetsPricesOnlyOnce(): void
    {
        $priceListName = 'msrp';

        $priceList = $this->createMock(PriceAttributePriceList::class);
        $priceList
            ->expects(static::exactly(2))
            ->method('getName')
            ->willReturn($priceListName);

        $repository = $this->createMock(PriceAttributeProductPriceRepository::class);
        $repository
            ->expects(static::once())
            ->method('deletePricesByPriceList')
            ->willReturn(self::DELETED_COUNT);

        $this->databaseHelper
            ->expects(static::any())
            ->method('findOneBy')
            ->willReturnMap(
                [
                    [PriceAttributePriceList::class, ['name' => $priceListName], $priceList],
                ]
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrineHelper
            ->expects(static::any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->context
            ->expects(static::once())
            ->method('incrementDeleteCount')
            ->with(self::DELETED_COUNT);

        $entity = new PriceAttributeProductPrice();
        $entity->setPriceList($priceList);

        $this->strategy->process($entity);
        $this->strategy->process($entity);
    }
}
