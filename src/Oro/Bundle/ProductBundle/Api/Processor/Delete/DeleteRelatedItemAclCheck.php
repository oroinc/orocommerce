<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ProductBundle\Api\Processor\Shared\RelatedItemAclCheck;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ChainProcessor\ContextInterface;

class DeleteRelatedItemAclCheck extends RelatedItemAclCheck
{
    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     * @return $this
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Because of Delete set ACL before the JOIN, and ACL is checked for DELETE action
     * we need to add additional ACL check for VIEW permission for related products after Query is created
     */
    public function process(ContextInterface $context)
    {
        parent::process($context);

        /** @var Context $context */
        $context->setQuery($this->aclHelper->apply($context->getQuery()));
    }
}
