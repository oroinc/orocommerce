<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnResolver;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnResolver\CheckoutSource\CheckoutSourceDefinition;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Translation\TranslatorInterface;

class StartedFromColumnResolver implements ColumnResolverInterface
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
     * @param TranslatorInterface $translator
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        BaseCheckoutRepository $baseCheckoutRepository,
        TranslatorInterface $translator,
        SecurityFacade $securityFacade
    ) {
        $this->baseCheckoutRepository = $baseCheckoutRepository;
        $this->translator = $translator;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function resolveColumn(OrmResultAfter $event)
    {
        $ids = [];

        foreach ($event->getRecords() as $record) {
            $ids[] = $record->getValue('id');
        }

        $sources = $this->baseCheckoutRepository->getSourcesByIds($ids);

        foreach ($event->getRecords() as $record) {
            if (isset($sources[$record->getValue('id')])) {
                $source = $sources[$record->getValue('id')];

                $sourceResult = null;

                if ($source instanceof ShoppingList) {
                    $sourceName = $source->getLabel();

                    $sourceResult = new CheckoutSourceDefinition(
                        $sourceName,
                        $this->hasCurrentUserRightToView($source),
                        'orob2b_shopping_list_frontend_view',
                        ['id' => $source->getId()]
                    );
                }

                if ($source instanceof Quote) {
                    $sourceName = $this->translator->trans(
                        'orob2b.frontend.sale.quote.orders_grid_label.label',
                        [
                            '%id%' => $source->getId()
                        ]
                    );

                    $sourceResult = new CheckoutSourceDefinition(
                        $sourceName,
                        $this->hasCurrentUserRightToView($source),
                        'orob2b_sale_quote_frontend_view',
                        ['id' => $source->getId()]
                    );
                }

                $record->addData(['startedFrom' => $sourceResult]);
            }
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
