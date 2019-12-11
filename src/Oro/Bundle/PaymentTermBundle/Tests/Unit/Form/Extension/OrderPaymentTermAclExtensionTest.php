<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PaymentTermBundle\Form\Extension\OrderPaymentTermAclExtension;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OrderPaymentTermAclExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderPaymentTermAclExtension */
    protected $extension;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentTermAssociationProvider;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    protected function setUp()
    {
        $this->paymentTermAssociationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->extension = new OrderPaymentTermAclExtension(
            $this->paymentTermAssociationProvider,
            $this->authorizationChecker
        );
        $this->extension->setAclResource('acl_res');
    }

    public function testGetExtendedTypes()
    {
        $this->assertSame([OrderType::class], OrderPaymentTermAclExtension::getExtendedTypes());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ACL resource not configured
     */
    public function testBuildWithoutAclResource()
    {
        $this->extension->setAclResource(null);
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildWithoutIsGrantedLeavePaymentTermFields()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);
        $this->paymentTermAssociationProvider->expects($this->never())->method($this->anything());

        $this->extension->buildForm($builder, []);
    }

    public function testBuildWithoutNotIsGrantedWithoitAssociations()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildWithoutNotIsGrantedShouldRemovePaymentTermFieldsWithoutField()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $builder->expects($this->once())->method('has')->willReturn(false);
        $builder->expects($this->never())->method('remove');

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }

    public function testBuildWithoutNotIsGrantedShouldRemovePaymentTermFields()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->paymentTermAssociationProvider->expects($this->once())->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $builder->expects($this->once())->method('has')->willReturn(true);
        $builder->expects($this->once())->method('remove')->with('paymentTerm');

        $this->extension->buildForm($builder, ['data_class' => \stdClass::class]);
    }
}