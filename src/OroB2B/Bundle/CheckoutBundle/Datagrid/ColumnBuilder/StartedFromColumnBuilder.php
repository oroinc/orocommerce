<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;
use OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder\CheckoutSource\CheckoutSourceDefinition;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class StartedFromColumnBuilder implements ColumnBuilderInterface
{
    /**
     * @var BaseCheckoutRepository
     */
    protected $baseCheckoutRepository;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var EntityNameResolver
     */
    private $entityNameResolver;

    /**
     * @param BaseCheckoutRepository $baseCheckoutRepository
     * @param SecurityFacade         $securityFacade
     * @param EntityNameResolver     $entityNameResolver
     */
    public function __construct(
        BaseCheckoutRepository $baseCheckoutRepository,
        SecurityFacade $securityFacade,
        EntityNameResolver $entityNameResolver
    ) {
        $this->baseCheckoutRepository = $baseCheckoutRepository;
        $this->securityFacade         = $securityFacade;
        $this->entityNameResolver     = $entityNameResolver;
    }

    /**
     * @param ResultRecord[] $records
     */
    public function buildColumn($records)
    {
        $ids = [];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        $sources = $this->baseCheckoutRepository->getSourcePerCheckout($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');
            if (!isset($sources[$id])) {
                continue;
            }

            $source = $sources[$id];

            if ($source instanceof QuoteDemand) {
                $source = $source->getQuote();
            }

            // simplify type checking in twig
            $type = $source instanceof ShoppingList ? 'shopping_list' : 'quote';
            $name = $this->entityNameResolver->getName($source);
            $data = [
                'linkable' => $this->hasCurrentUserRightToView($source),
                'type'     => $type,
                'label'    => $name,
                'id'       => $source->getId()
            ];

            $record->addData(['startedFrom' => $data]);
        }
    }

    /**
     * @param $sourceEntity
     * @return bool
     */
    protected function hasCurrentUserRightToView($sourceEntity)
    {
        $isGranted = $this->securityFacade->isGranted('ACCOUNT_VIEW', $sourceEntity);

        return $isGranted === true || $isGranted === "true"; // isGranted may return "true" as string
    }
}
