<?php

namespace ETS\Payment\DotpayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type for Dotpay direct payment method.
 *
 * @author ETSGlobal <e4-devteam@etsglobal.org>
 */
class DirectType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder The builder
     * @param array                $options Options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dotpay_direct';
    }
}