<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceRuleLexemeHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject */
    private $parser;

    /** @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldsProvider;

    /** @var PriceRuleLexemeHandler */
    private $priceRuleLexemeHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->parser = $this->createMock(ExpressionParser::class);
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);

        $this->priceRuleLexemeHandler = new PriceRuleLexemeHandler(
            $this->doctrineHelper,
            $this->parser,
            $this->fieldsProvider
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateLexemes(): void
    {
        $organization = new Organization();
        $organization->setId(489);

        $assignmentRule = 'category.id == 2 or category == 10 or pricelist[4].prices.value == 50';
        $rule = 'product.msrp.value + 10';
        $ruleCondition = 'product.sku == test';

        $qtyExpr = 'product.sell_qty';
        $unitExpr = 'product.sell_unit';
        $currencyExpr = 'product.sell_currency';

        $priceRule = $this->getEntity(PriceRule::class, ['id' => 1]);
        $priceRule->setRule($rule);
        $priceRule->setRuleCondition($ruleCondition);
        $priceRule->setQuantityExpression($qtyExpr);
        $priceRule->setProductUnitExpression($unitExpr);
        $priceRule->setCurrencyExpression($currencyExpr);

        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setProductAssignmentRule($assignmentRule);
        $priceList->addPriceRule($priceRule);
        $priceList->setOrganization($organization);

        $this->parser->expects($this->any())
            ->method('getUsedLexemes')
            ->willReturnMap([
                [
                    $assignmentRule,
                    true,
                    [
                        'Oro\Bundle\ProductBundle\Entity\Category' => ['id', null],
                        'Oro\Bundle\PricingBundle\Entity\PriceList::prices|4' => ['value']
                    ]
                ],
                [$rule, true, ['Oro\Bundle\ProductBundle\Entity\Product::msrp' => ['value']]],
                [$ruleCondition, true, ['Oro\Bundle\ProductBundle\Entity\Product' => ['sku']]],
                [$qtyExpr, true, ['Oro\Bundle\ProductBundle\Entity\Product' => ['sell_qty']]],
                [$unitExpr, true, ['Oro\Bundle\ProductBundle\Entity\Product' => ['sell_unit']]],
                [$currencyExpr, true, ['Oro\Bundle\ProductBundle\Entity\Product' => ['sell_currency']]]
            ]);

        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->willReturnMap([
                ['Oro\Bundle\ProductBundle\Entity\Category', null, 'Oro\Bundle\ProductBundle\Entity\Category'],
                [
                    'Oro\Bundle\ProductBundle\Entity\Product::msrp',
                    null,
                    'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice'
                ],
                ['Oro\Bundle\ProductBundle\Entity\Product', null, 'Oro\Bundle\ProductBundle\Entity\Product'],
                [
                    'Oro\Bundle\PricingBundle\Entity\PriceList::prices',
                    null,
                    ProductPrice::class
                ]
            ]);

        $lexemeRepository = $this->createMock(PriceRuleLexemeRepository::class);
        $lexemeRepository->expects($this->once())
            ->method('deleteByPriceList')
            ->with($priceList);

        $lexemeEntityManager = $this->createMock(EntityManager::class);
        $lexemeEntityManager->expects($this->once())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($lexemeRepository);

        $msrpId = '42';
        $msrpPriceAttribute = $this->getEntity(PriceAttributePriceList::class, ['id' => $msrpId]);

        $priceAttributeRepository = $this->createMock(PriceRuleLexemeRepository::class);
        $priceAttributeRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['fieldName' => 'msrp', 'organization' => $organization])
            ->willReturn($msrpPriceAttribute);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with(PriceRuleLexeme::class)
            ->willReturn($lexemeEntityManager);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(PriceAttributePriceList::class)
            ->willReturn($priceAttributeRepository);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');

        $lexemeEntityManager->expects($this->exactly(10))
            ->method('persist');
        $lexemeEntityManager->expects($this->once())
            ->method('flush');

        $this->priceRuleLexemeHandler->updateLexemes($priceList);
    }
}
