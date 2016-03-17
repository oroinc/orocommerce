<?php

namespace OroB2B\Bundle\CheckoutBundle\Model\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActionBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Start checkout process on frontend
 *
 * Usage:
 *
 * @start_checkout:
 *     source_name: shoppingList
 *     source_entity: $.data
 *     data:
 *         currency: $.currency
 *     settings:
 *          allow_source_remove: false
 *
 * source_name (required) is name of corresponding extended relation added to CheckoutSource
 * source_entity (required) is a source entity
 * settings (optional) are to WorkflowItem data and are used during checkout process
 * data (optional) is passed as properties of Checkout entity
 */
class StartCheckout extends AbstractAction
{
    const SOURCE_FIELD_KEY = 'source_name';
    const SOURCE_ENTITY_KEY = 'source_entity';
    const CHECKOUT_DATA_KEY = 'data';
    const SETTINGS_KEY = 'settings';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $checkoutClass;

    /**
     * @var string
     */
    protected $checkoutRoute;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var AbstractAction
     */
    protected $redirect;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     * @param WebsiteManager $websiteManager
     * @param UserCurrencyProvider $currencyProvider
     * @param TokenStorageInterface $tokenStorage
     * @param PropertyAccessor $propertyAccessor
     * @param AbstractAction $redirect
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        WebsiteManager $websiteManager,
        UserCurrencyProvider $currencyProvider,
        TokenStorageInterface $tokenStorage,
        PropertyAccessor $propertyAccessor,
        AbstractAction $redirect
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
        $this->currencyProvider = $currencyProvider;
        $this->tokenStorage = $tokenStorage;
        $this->propertyAccessor = $propertyAccessor;
        $this->redirect = $redirect;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::SOURCE_FIELD_KEY])) {
            throw new InvalidParameterException('Source parameter is required');
        }

        if (empty($options[self::SOURCE_ENTITY_KEY])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param string $checkoutClass
     * @return StartCheckout
     */
    public function setCheckoutClass($checkoutClass)
    {
        $this->checkoutClass = $checkoutClass;

        return $this;
    }

    /**
     * @param string $checkoutRoute
     * @return StartCheckout
     */
    public function setCheckoutRoute($checkoutRoute)
    {
        $this->checkoutRoute = $checkoutRoute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $em = $this->getEntityManager();

        $sourceFieldName = $this->contextAccessor->getValue($context, $this->options[self::SOURCE_FIELD_KEY]);
        /** @var CheckoutSourceEntityInterface $sourceEntity */
        $sourceEntity = $this->contextAccessor->getValue($context, $this->options[self::SOURCE_ENTITY_KEY]);

        $checkoutSource = $em->getRepository('OroB2BCheckoutBundle:CheckoutSource')
            ->findOneBy([$sourceFieldName => $sourceEntity]);

        if ($checkoutSource) {
            $checkout = $em->getRepository('OroB2BCheckoutBundle:Checkout')
                ->findOneBy(['source' => $checkoutSource]);

        } else {
            $checkoutSource = $this->createCheckoutSource($sourceFieldName, $sourceEntity);
            $checkout = $this->createCheckout($context, $checkoutSource);
            $em->persist($checkout);
            $em->flush($checkout);

            $this->addWorkflowItemDataSettings($context, $checkout->getWorkflowItem());
            $em->flush($checkout->getWorkflowItem());
        }

        $this->redirect->initialize(
            [
                'route' => $this->checkoutRoute,
                'route_parameters' => ['id' => $checkout->getId()]
            ]
        );
        $this->redirect->execute($context);
    }

    /**
     * @param mixed $context
     * @param WorkflowItem $workflowItem
     */
    protected function addWorkflowItemDataSettings($context, WorkflowItem $workflowItem)
    {
        $settings = $this->getOptionFromContext($context, self::SETTINGS_KEY, []);

        if (is_array($settings) && $settings) {
            $workflowData = $workflowItem->getData();
            foreach ($settings as $key => $setting) {
                $workflowData->set($key, $setting);
            }
            $workflowItem->setUpdated();
        }
    }

    /**
     * @param string $sourceFieldName
     * @param CheckoutSourceEntityInterface $sourceEntity
     * @return CheckoutSource
     */
    protected function createCheckoutSource($sourceFieldName, CheckoutSourceEntityInterface $sourceEntity)
    {
        $checkoutSource = new CheckoutSource();
        $this->propertyAccessor->setValue($checkoutSource, $sourceFieldName, $sourceEntity);

        return $checkoutSource;
    }

    /**
     * @param mixed $context
     * @param CheckoutSource $checkoutSource
     * @return Checkout
     */
    protected function createCheckout($context, CheckoutSource $checkoutSource)
    {
        /** @var AccountUser $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $account = $user->getAccount();
        $owner = $account->getOwner();
        $organization = $account->getOrganization();
        $defaultData = [
            'accountUser' => $user,
            'account' => $account,
            'owner' => $owner,
            'organization' => $organization,
            'website' => $this->websiteManager->getCurrentWebsite(),
            'currency' => $this->currencyProvider->getUserCurrency()
        ];

        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        $this->setCheckoutData($context, $checkout, $defaultData);

        return $checkout;
    }

    /**
     * @param mixed $context
     * @param Checkout $checkout
     * @param array $defaultData
     */
    protected function setCheckoutData($context, Checkout $checkout, array $defaultData)
    {
        $data = $this->getOptionFromContext($context, self::CHECKOUT_DATA_KEY, []);
        $data = array_filter(
            $data,
            function ($element) {
                return $element !== null;
            }
        );
        $data = array_merge($defaultData, $data);

        foreach ($data as $property => $value) {
            if ($this->propertyAccessor->isWritable($checkout, $property)) {
                $this->propertyAccessor->setValue($checkout, $property, $value);
            }
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManagerForClass($this->checkoutClass);
        }

        return $this->em;
    }

    /**
     * @param mixed $context
     * @param string $key
     * @param null|mixed $default
     * @return array|mixed
     */
    protected function getOptionFromContext($context, $key, $default = null)
    {
        $data = $default;
        if (array_key_exists($key, $this->options)) {
            $data = $this->contextAccessor->getValue($context, $this->options[$key]);
        }

        if ($data) {
            foreach ($data as &$value) {
                $value = $this->contextAccessor->getValue($context, $value);
            }
        }

        return $data;
    }
}
