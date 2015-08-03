<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionArgsParser as ArgsParser;

class AddProductsMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var ObjectManager
     */
    protected $productEm;

    /**
     * @param ManagerRegistry     $managerRegistry
     * @param ShoppingListManager $shoppingListManager
     * @param TranslatorInterface $translator
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ShoppingListManager $shoppingListManager,
        TranslatorInterface $translator,
        SecurityFacade $securityFacade
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->translator = $translator;
        $this->securityFacade = $securityFacade;
        $this->productEm = $managerRegistry->getManagerForClass('OroB2BProductBundle:Product');
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $argsParser = new ArgsParser($args);
        $shoppingList = $this->shoppingListManager->getForCurrentUser($argsParser->getShoppingListId());

        if (!$this->securityFacade->isGranted('EDIT', $shoppingList)) {
            return $this->generateResponse($args);
        }

        /** @var ProductRepository $productsRepo */
        $productsRepo = $this->productEm->getRepository('OroB2BProductBundle:Product');

        $iterableResult = $productsRepo
            ->getProductsQueryBuilder($argsParser->getProductIds())
            ->getQuery()
            ->iterate();

        $lineItems = [];
        foreach ($iterableResult as $entity) {
            /** @var Product $entity */
            $entity = $entity[0];
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $entity->getUnitPrecisions()->first();
            $lineItem = (new LineItem())
                ->setOwner($shoppingList->getOwner())
                ->setOrganization($shoppingList->getOrganization())
                ->setProduct($entity)
                ->setQuantity(1)
                ->setUnit($unitPrecision->getUnit());
            $lineItems[] = $lineItem;
        }

        $addedCnt = $this->shoppingListManager
            ->bulkAddLineItems($lineItems, $shoppingList, self::FLUSH_BATCH_SIZE);

        return $this->generateResponse($args, $addedCnt);
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int                   $entitiesCount
     *
     * @return MassActionResponse
     */
    protected function generateResponse(MassActionHandlerArgs $args, $entitiesCount = 0)
    {
        return new MassActionResponse(
            $entitiesCount > 0,
            $this->translator->transChoice(
                $args->getMassAction()->getOptions()->offsetGetByPath(
                    '[messages][success]',
                    'orob2b.shoppinglist.actions.add_success_message'
                ),
                $entitiesCount,
                ['%count%' => $entitiesCount]
            ),
            ['count' => $entitiesCount]
        );
    }
}
