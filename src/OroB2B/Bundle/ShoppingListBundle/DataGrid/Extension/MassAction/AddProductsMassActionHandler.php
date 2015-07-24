<?php
namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class AddProductsMassActionHandler implements MassActionHandlerInterface
{
    const CURRENT_SHOPPING_LIST_KEY = 'current';
    const FLUSH_BATCH_SIZE = 100;

    /** @var  EntityManager */
    protected $entityManager;
    /** @var ShoppingListManager */
    protected $shoppingListManager;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var SecurityContext */
    protected $securityContext;
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /**
     * @param EntityManager       $entityManager
     * @param ShoppingListManager $shoppingListManager
     * @param TranslatorInterface $translator
     * @param SecurityContext     $securityContext
     * @param ServiceLink         $securityFacadeLink
     */
    public function __construct(
        EntityManager $entityManager,
        ShoppingListManager $shoppingListManager,
        TranslatorInterface $translator,
        SecurityContext $securityContext,
        ServiceLink $securityFacadeLink
    ) {
        $this->entityManager = $entityManager;
        $this->shoppingListManager = $shoppingListManager;
        $this->translator = $translator;
        $this->securityContext = $securityContext;
        $this->securityFacadeLink = $securityFacadeLink;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $data = $args->getData();
        $isAllSelected = $this->isAllSelected($args->getData());
        $shoppingList = $this->getShoppingList($data['shoppingList']);
        $productIds = !$isAllSelected && array_key_exists('values', $data) ? explode(',', $data['values']) : [];

        $iterableResult = $this->getProductsQueryBuilder($productIds)->getQuery()->iterate();

        $iteration = 0;
        /** @var Product $entity */
        foreach ($iterableResult as $iteration => $entity) {
            $entity = $entity[0];
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $entity->getUnitPrecisions()->first();
            $lineItem = (new LineItem())
                ->setProduct($entity)
                ->setQuantity(1)
                ->setUnit($unitPrecision->getUnit());
            $flush = ($iteration % self::FLUSH_BATCH_SIZE) === 0;
            $this->shoppingListManager->addLineItem($lineItem, $shoppingList, $flush);
        }
        $this->entityManager->flush();

        return $this->getResponse($args, $iteration > 0 ? ++$iteration : $iteration);
    }

    /**
     * @param array $productIds
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getProductsQueryBuilder($productIds = [])
    {
        $productsQueryBuilder = $this->entityManager
            ->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('p');

        $productsQueryBuilder->select('p');
        if (count($productIds) > 0) {
            $productsQueryBuilder
                ->where('p IN (:product_ids)')
                ->setParameter('product_ids', $productIds);
        }

        return $productsQueryBuilder;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isAllSelected(array $data)
    {
        return array_key_exists('inset', $data) && $data['inset'] === '0';
    }

    /**
     * @param int|string $shoppingList
     *
     * @return ShoppingList|null
     */
    protected function getShoppingList($shoppingList)
    {
        $user = $this->securityContext->getToken()->getUser();
        $isCurrent = $shoppingList === self::CURRENT_SHOPPING_LIST_KEY;
        $repository = $this->entityManager->getRepository('OroB2BShoppingListBundle:ShoppingList');

        $shoppingList = !$isCurrent
            ? $repository->findByUserAndId($user, $shoppingList)
            : $repository->findCurrentForAccountUser($user);

        if (!$shoppingList instanceof ShoppingList) {
            $shoppingList = $this->shoppingListManager->createCurrent();
        }

        return $shoppingList;
    }

    /**
     * @param MassActionHandlerArgs $args
     * @param int                   $entitiesCount
     *
     * @return MassActionResponse
     */
    protected function getResponse(MassActionHandlerArgs $args, $entitiesCount = 0)
    {
        $massAction = $args->getMassAction();
        $responseMessage = $massAction->getOptions()->offsetGetByPath(
            '[messages][success]',
            'orob2b.shoppinglist.actions.add_success_message'
        );

        $successful = $entitiesCount > 0;
        $options = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->transChoice(
                $responseMessage,
                $entitiesCount,
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }
}
