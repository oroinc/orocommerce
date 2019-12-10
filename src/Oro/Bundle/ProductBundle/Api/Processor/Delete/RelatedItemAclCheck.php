<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ProductBundle\Api\Processor\RelatedItemAclCheck as BaseRelatedItemAclCheck;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Modifies the query that is used to retrieve a related product to be deleted
 * to return only relations for which both products in relation are accessible by an user,
 * and adds an additional ACL check for VIEW permission for the query.
 * It is required because of Delete action sets ACL before the query is modified.
 */
class RelatedItemAclCheck extends BaseRelatedItemAclCheck
{
    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        parent::process($context);

        $context->setQuery($this->aclHelper->apply($context->getQuery()));
    }
}
