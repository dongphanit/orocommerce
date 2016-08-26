<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductPriceCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.product_price.currency.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing_product_price_currency_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
