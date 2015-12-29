=====
Usage
=====
With the Payment Plugin Controller (Recommended)
------------------------------------------------
http://jmsyst.com/bundles/JMSPaymentCoreBundle/master/usage

You can configure some custom fields :

.. code-block :: php

    <?php

    class PaymentController
    {
        ...

        $form = $this->getFormFactory()->create('jms_choose_payment_method', null, array(
                'amount'   => $order->getAmount(),
                'currency' => 'EUR',
                'default_method' => 'dotpay_direct', // Optional
                'predefined_data' => array(
                    'dotpay_direct' => array(
                        'street'    => 'Customer\'s address street line',    // Optional
                        'phone'     => 'Customer phone number',             // Optional
                        'postcode'  => 'Customer address postal code',      // Optional
                        'lastname'  => 'Customer lastname',                 // Optional
                        'firstname' => 'Customer firstname',                // Optional
                        'email'     => 'Customer email',                    // Optional
                        'country'   => 'Customer country',                  // Optional
                        'city'      => 'Customer city',                     // Optional
                        'lang'      => $request->getLocale(),               // Optional
                        'return_url' => $this->router->generate('payment_complete', array(
                            'orderNumber' => $order->getOrderNumber(),
                        ), true),
                    ),
                ),
            ));

        ...
    }

Without the Payment Plugin Controller
-------------------------------------
The Payment Plugin Controller is made available by the CoreBundle and basically is the
interface to a persistence backend like the Doctrine ORM. It also performs additional
integrity checks to validate transactions. If you don't need these checks, and only want
an easy way to communicate with the Dotpay API, then you can use the plugin directly::

    $plugin = $container->get('payment.plugin.dotpay_direct');

.. _JMSPaymentCoreBundle: https://github.com/schmittjoh/JMSPaymentCoreBundle/blob/master/Resources/doc/index.rst
