<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder;

use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder\CheckoutSource\CheckoutSourceDefinition;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class StartedFromColumnBuilder implements ColumnBuilderInterface
{
    /**
     * @var BaseCheckoutRepository
     */
    private $baseCheckoutRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * LineItemsCountColumnResolver constructor.
     * @param BaseCheckoutRepository $baseCheckoutRepository
     * @param TranslatorInterface    $translator
     * @param SecurityFacade         $securityFacade
     */
    public function __construct(
        BaseCheckoutRepository $baseCheckoutRepository,
        TranslatorInterface $translator,
        SecurityFacade $securityFacade
    ) {
        $this->baseCheckoutRepository = $baseCheckoutRepository;
        $this->translator             = $translator;
        $this->securityFacade         = $securityFacade;
    }

    /**
     * @param ResultRecord[] $records
     */
    public function buildColumn($records)
    {
        $ids = [ ];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        $sources = $this->baseCheckoutRepository->getSourcePerCheckout($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');
            if (!isset($sources[$id])) {
                continue;
            }

            $source     = $sources[$id];
            $sourceName = null;

            // @todo Refactor this: https://magecore.atlassian.net/browse/BB-3614
            if ($source instanceof ShoppingList) {
                $sourceName = $source->getLabel();
            }

            if ($source instanceof Quote || $source instanceof QuoteDemand) {
                $sourceName = $this->translator->trans(
                    'orob2b.frontend.sale.quote.title.label',
                    [
                        '%id%' => $source->getId()
                    ]
                );
            }

            $sourceResult = new CheckoutSourceDefinition(
                $sourceName,
                $this->hasCurrentUserRightToView($source),
                'orob2b_shopping_list_frontend_view',
                [ 'id' => $source->getId() ]
            );

            $record->addData([ 'startedFrom' => $sourceResult ]);
        }
    }

    /**
     * @param $sourceEntity
     * @return bool
     */
    private function hasCurrentUserRightToView($sourceEntity)
    {
        $isGranted = $this->securityFacade->isGranted('ACCOUNT_VIEW', $sourceEntity);

        return $isGranted === true || $isGranted === "true"; // isGranted may return "true" as string
    }
}
