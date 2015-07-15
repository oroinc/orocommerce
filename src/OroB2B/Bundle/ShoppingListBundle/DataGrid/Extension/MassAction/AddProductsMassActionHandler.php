<?php
namespace OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class AddProductsMassActionHandler implements MassActionHandlerInterface
{
    const CURRENT_SHOPPING_LIST_KEY = 'current';

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
        $user = $this->securityContext->getToken()->getUser();
        $isAllSelected = $this->isAllSelected($args->getData());
        $shoppingList = $this->getShoppingList($data['shoppingList']);
        $productIds = !$isAllSelected && array_key_exists('data', $data) ? explode(',', $data['data']) : [];

        $iterableResult = $this->getProductsQueryBuilder($productIds)->getQuery()->iterate();

        foreach ($iterableResult as $entity) {
            $entity = $entity[0];
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

        return !$isCurrent
            ? $repository->findByUserAndId($user, $shoppingList)
            : $repository->findCurrentForAccountUser($user);
    }
}
