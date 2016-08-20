<?php

namespace Oro\Bundle\WebsiteBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class AssignCurrentWebsite extends AbstractAction
{
    /**
     * @var PropertyPathInterface
     */
    protected $attribute;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param WebsiteManager $websiteManager
     */
    public function __construct(ContextAccessor $contextAccessor, WebsiteManager $websiteManager)
    {
        parent::__construct($contextAccessor);

        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue($context, $this->attribute, $this->websiteManager->getCurrentWebsite());
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) !== 1) {
            throw new InvalidParameterException('Only one attribute parameter must be defined');
        }

        $attribute = null;
        if (array_key_exists(0, $options)) {
            $attribute = $options[0];
        } elseif (array_key_exists('attribute', $options)) {
            $attribute = $options['attribute'];
        }

        if (!$attribute) {
            throw new InvalidParameterException('Attribute must be defined');
        }
        if (!$attribute instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        $this->attribute = $attribute;

        return $this;
    }
}
