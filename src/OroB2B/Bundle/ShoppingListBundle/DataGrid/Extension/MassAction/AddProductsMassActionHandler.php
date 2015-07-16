<?php
namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

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
     * @param EntityManager $entityManager
     * @param ShoppingListManager $shoppingListManager
     * @param TranslatorInterface $translator
     * @param SecurityContext $securityContext
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(
        EntityManager $entityManager,
        ShoppingListManager $shoppingListManager,
        TranslatorInterface $translator,
        SecurityContext $securityContext,
        ServiceLink $securityFacadeLink
    )
    {
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
        $productIds = !$isAllSelected && array_key_exists('data', $data) ? explode(',', $data['data']) : [];

        $iterableResult = $this->getProductsQueryBuilder($productIds)->getQuery()->iterate();

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

        die('handle AddProductsMassActionHandler');
    }

    /**
     * @param array $productIds
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getProductsQueryBuilder($productIds = [])
    {
        $productsQueryBuilder = $this->entityManager
            ->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('p')
            ->select('p');
        if (count($productIds) > 0) {
            $productsQueryBuilder
                ->where('p MEMBER OF :product_ids')
                ->setParameter('product_ids', $productIds);
        }

        return $productsQueryBuilder;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isAllSelected($data)
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
}
