<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Placeholder\CustomerIdPlaceholder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVisibilitySearchQueryModifierTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var PlaceholderProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholderProvider;

    /** @var ProductVisibilitySearchQueryModifier */
    private $modifier;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->placeholderProvider = $this->createMock(PlaceholderProvider::class);

        $this->modifier = new ProductVisibilitySearchQueryModifier(
            $this->tokenStorage,
            $this->placeholderProvider
        );
    }

    public function testModify()
    {
        $this->placeholderProvider->expects($this->once())
            ->method('getPlaceholderFieldName')
            ->with(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                    CustomerIdPlaceholder::NAME => 1
                ]
            )
            ->willReturn('visibility_customer_1');

        $customer = new Customer();
        ReflectionUtil::setId($customer, 1);

        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $query = new Query();
        $this->modifier->modify($query);

        $hidden = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        $visible = BaseVisibilityResolved::VISIBILITY_VISIBLE;

        $expected = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($visible)),
                        new SearchComparison(
                            'integer.visibility_customer_1',
                            SearchComparison::NOT_EXISTS,
                            new Value(null)
                        ),
                    ]
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($hidden)),
                        new Comparison('integer.visibility_customer_1', Comparison::EQ, new Value($visible)),
                    ]
                ),
            ]
        );

        $this->assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }

    public function wrongCustomerUserProvider(): array
    {
        return [
            [null],
            [new \stdClass()]
        ];
    }

    /**
     * @dataProvider wrongCustomerUserProvider
     */
    public function testModifyForAnonymous(mixed $customerUser)
    {
        $this->placeholderProvider->expects($this->never())
            ->method('getPlaceholderFieldName');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $expected = new Comparison(
            'integer.visibility_anonymous',
            Comparison::EQ,
            new Value(BaseVisibilityResolved::VISIBILITY_VISIBLE)
        );

        $query = new Query();
        $this->modifier->modify($query);

        $this->assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }
}
