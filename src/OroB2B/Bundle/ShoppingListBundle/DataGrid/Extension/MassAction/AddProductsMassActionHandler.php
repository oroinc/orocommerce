<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;
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
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param ManagerRegistry     $managerRegistry
     * @param ShoppingListManager $shoppingListManager
     * @param TranslatorInterface $translator
     * @param SecurityFacade      $securityFacade
     * @param RouterInterface     $router
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ShoppingListManager $shoppingListManager,
        TranslatorInterface $translator,
        SecurityFacade $securityFacade,
        RouterInterface $router
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->translator = $translator;
        $this->securityFacade = $securityFacade;
        $this->productEm = $managerRegistry->getManagerForClass('OroB2BProductBundle:Product');
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $argsParser = new ArgsParser($args);
        $shoppingList = $this->shoppingListManager->getForCurrentUser($argsParser->getShoppingListId());

        if (!$this->securityFacade->isGranted('EDIT', $shoppingList)
            || !$this->securityFacade->isGranted('orob2b_shopping_list_line_item_create')
        ) {
            return $this->generateResponse($args);
        }

        /** @var ProductRepository $productsRepo */
        $productsRepo = $this->productEm->getRepository('OroB2BProductBundle:Product');

        $iterableResult = $productsRepo
            ->getProductsQueryBuilder($argsParser->getProductIds())
            ->getQuery()
            ->iterate();

        $lineItems = [];
        foreach ($iterableResult as $entityArray) {
            /** @var Product $entity */
            $entity = $entityArray[0];
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $entity->getUnitPrecisions()->first();

            $lineItems[] = (new LineItem())
                ->setOwner($shoppingList->getOwner())
                ->setOrganization($shoppingList->getOrganization())
                ->setProduct($entity)
                ->setQuantity(1)
                ->setUnit($unitPrecision->getUnit());
        }

        $addedCnt = $this->shoppingListManager
            ->bulkAddLineItems($lineItems, $shoppingList, self::FLUSH_BATCH_SIZE);

        return $this->generateResponse($args, $addedCnt, $shoppingList->getId());
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int                   $entitiesCount
     * @param int|null              $shoppingListId
     *
     * @return MassActionResponse
     */
    protected function generateResponse(MassActionHandlerArgs $args, $entitiesCount = 0, $shoppingListId = null)
    {
        $message = $this->translator->transChoice(
            $args->getMassAction()->getOptions()->offsetGetByPath(
                '[messages][success]',
                'orob2b.shoppinglist.actions.add_success_message'
            ),
            $entitiesCount,
            ['%count%' => $entitiesCount]
        );

        if ($shoppingListId && $entitiesCount > 0) {
            $link = $this->router->generate('orob2b_shopping_list_frontend_view', ['id' => $shoppingListId]);
            $message .= $this->translator->trans(
                'orob2b.shoppinglist.actions.add_success_message_link',
                ['%link%' => $link]
            );
        }

        return new MassActionResponse(
            $entitiesCount > 0,
            $message,
            ['count' => $entitiesCount]
        );
    }
}
