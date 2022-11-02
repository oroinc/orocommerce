<?php

namespace Oro\Bundle\PricingBundle\Action;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use Oro\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * This action is responsible for recalculation of price lists in background (via message queue)
 */
class PriceListBackgroundRecalculateAction extends AbstractAction
{
    const NAME = 'price_list_recalculate';

    const OPTION_KEY_PRICE_LIST = 'price_list';

    /** @var PriceListProductAssignmentBuilder */
    protected $assignmentBuilder;

    /** @var ProductPriceBuilder */
    protected $priceBuilder;

    /** @var DependentPriceListProvider */
    protected $dependentPriceListProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyPathInterface */
    protected $priceListOption;

    public function __construct(
        ContextAccessor $contextAccessor,
        PriceListProductAssignmentBuilder $assignmentBuilder,
        ProductPriceBuilder $priceBuilder,
        DependentPriceListProvider $dependentPriceListProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->assignmentBuilder = $assignmentBuilder;
        $this->priceBuilder = $priceBuilder;
        $this->dependentPriceListProvider = $dependentPriceListProvider;
        $this->doctrineHelper = $doctrineHelper;
        parent::__construct($contextAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_PRICE_LIST])) {
            throw new InvalidParameterException(sprintf(
                "Required option '%s' is empty",
                self::OPTION_KEY_PRICE_LIST
            ));
        }
        $this->priceListOption = $options[self::OPTION_KEY_PRICE_LIST];
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $priceList = $this->contextAccessor->getValue($context, $this->priceListOption);
        if (!$priceList instanceof PriceList) {
            throw new InvalidParameterException(sprintf(
                "Action '%s' expects option '%s' to be instance of '%s', but '%s' given instead",
                self::NAME,
                self::OPTION_KEY_PRICE_LIST,
                PriceList::class,
                ClassUtils::getClass($priceList)
            ));
        }
        $this->recalculate($priceList);
    }

    protected function recalculate(PriceList $rootPriceList)
    {
        $priceLists = $this->dependentPriceListProvider->appendDependent([$rootPriceList]);
        foreach ($priceLists as $priceList) {
            $this->assignmentBuilder->buildByPriceListWithoutEventDispatch($priceList);
            $this->priceBuilder->buildByPriceList($priceList);
            $priceList->setActual(true);
        }
        $this->doctrineHelper->getEntityManagerForClass(PriceList::class)->flush($priceLists);
    }
}
