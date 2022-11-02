<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Adds field 'prices' on product edit form and process changes of product prices
 */
class ProductFormExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var PriceManager */
    protected $priceManager;

    /** @var ShardManager */
    protected $shardManager;

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager,
        PriceManager $priceManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->registry = $registry;
        $this->shardManager = $shardManager;
        $this->priceManager = $priceManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'addFormOnPreSetData']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'], 10);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    public function addFormOnPreSetData(FormEvent $event)
    {
        /** @var Product|null $product */
        $product = $event->getData();
        $form = $event->getForm();

        if ($form->has('prices')) {
            return;
        }

        $isAllowToCreate = $this->isPermissionsGranted(['CREATE']);
        if (!$product || !$product->getId()) {
            if ($isAllowToCreate) {
                $this->addForm($form, $product);
            }

            return;
        }

        if ($this->isPermissionsGranted(['EDIT', 'VIEW'])) {
            $this->addForm(
                $form,
                $product,
                $isAllowToCreate,
                $this->isPermissionsGranted(['DELETE'])
            );

            return;
        }
    }

    /**
     * @param FormInterface $form
     * @param Product|null  $product
     * @param bool          $allowAdd
     * @param bool          $allowDelete
     */
    protected function addForm(FormInterface $form, Product $product = null, $allowAdd = true, $allowDelete = true)
    {
        $form->add(
            'prices',
            ProductPriceCollectionType::class,
            [
                'label' => 'oro.pricing.productprice.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new UniqueProductPrices(['groups' => [ProductPriceCollectionType::VALIDATION_GROUP]]),
                    // Valid constraint added to allow cascade validation of the prices on the backend
                    new Valid(['groups' => [ProductPriceCollectionType::VALIDATION_GROUP]])
                ],
                'entry_options' => [
                    'product' => $product,
                ],
                'allow_add' => $allowAdd,
                'allow_delete' => $allowDelete,
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product || !$product->getId() || !$form->has('prices')) {
            return;
        }

        $prices = $this->getProductPriceRepository()->getPricesByProduct($this->shardManager, $product);

        $form->get('prices')->setData($prices);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Product|null $product */
        $product = $form->getData();
        if (!$product || !$product->getId() || !$form->has('prices')) {
            return;
        }

        $submittedData = $event->getData();

        if (array_key_exists('prices', $submittedData)) {
            $event->setData($this->getReplacedPricesByUniqueAttributes($product, $submittedData));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Product|null $product */
        $form = $event->getForm();
        $product = $event->getData();
        if (!$product || !$form->has('prices')) {
            return;
        }

        /** @var ProductPrice[] $prices */
        $prices = (array)$form->get('prices')->getData();
        foreach ($prices as $price) {
            $price->setProduct($product);
        }

        if (!$form->isValid()) {
            return;
        }

        $this->processPrices($prices, $product);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->getManager()
            ->getRepository('OroPricingBundle:ProductPrice');
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass('OroPricingBundle:ProductPrice');
    }

    /**
     * @param array        $submitted
     * @param ProductPrice $existing
     *
     * @return boolean
     */
    protected function assertUniqueAttributes(array $submitted, ProductPrice $existing)
    {
        if ($submitted['priceList'] !== (string)$existing->getPriceList()->getId()) {
            return false;
        }
        if ($submitted['price']['currency'] !== $existing->getPrice()->getCurrency()) {
            return false;
        }
        if ($submitted['quantity'] !== (string)$existing->getQuantity()) {
            return false;
        }
        if ($submitted['unit'] !== $existing->getUnit()->getCode()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $permissions
     *
     * @return bool
     */
    private function isPermissionsGranted(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->authorizationChecker->isGranted(
                $permission,
                sprintf('entity:%s', ProductPrice::class)
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Product $product
     * @param array $submittedData
     * @return array
     */
    private function getReplacedPricesByUniqueAttributes(Product $product, array $submittedData)
    {
        $submittedPrices = $submittedData['prices'];
        $submittedPricesKeys = array_keys($submittedPrices);

        $replacedPrices = [];
        $existingPrices = $this->getProductPriceRepository()->getPricesByProduct($this->shardManager, $product);

        foreach ($existingPrices as $key => $price) {
            if (!in_array($key, $submittedPricesKeys)) {
                unset($existingPrices[$key]);
            }
        }

        foreach ($submittedPrices as $key => $submittedPrice) {
            foreach ($existingPrices as $k => $existingPrice) {
                if ($key !== $k && $this->assertUniqueAttributes($submittedPrice, $existingPrice)) {
                    $replacedPrices[$k] = $submittedPrice;
                    break;
                }
            }
        }

        $correctPrices = array_replace($submittedPrices, $replacedPrices);
        $submittedData['prices'] = $correctPrices;

        return $submittedData;
    }

    private function processPrices(array $prices, Product $product)
    {
        $repository = $this->getProductPriceRepository();
        // persist existing prices
        $persistedPriceIds = [];

        foreach ($prices as $price) {
            $priceId = $price->getId();
            if ($priceId) {
                $persistedPriceIds[] = $priceId;
            }

            $price->setProduct($product);
            $this->priceManager->persist($price);
        }

        // remove deleted prices
        if ($product->getId()) {
            $existingPrices = $repository->getPricesByProduct($this->shardManager, $product);
            foreach ($existingPrices as $price) {
                if (!in_array($price->getId(), $persistedPriceIds, true)) {
                    $this->priceManager->remove($price);
                }
            }
        }
    }
}
