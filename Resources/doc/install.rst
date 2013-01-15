============
Installation
============
Dependencies
------------
This plugin depends on the JMSPaymentCoreBundle_, so you'll need to add this to your kernel
as well even if you don't want to use its persistence capabilities.

Dotpay configuration
--------------------

.. warning ::
    You will have to allow the URLC external use in your dotpay account in Settings → URLC parameters :
    [x] Permits to receive URLC parameter from external services


Configuration
-------------
::

    // YAML
    ets_payment_dotpay:
        direct:
            id: your seller id
            pin: your URLC PIN number (PIN number should consist of 16 characters)
            url: the paypal url (Optional, default : https://ssl.dotpay.pl/ )
            type: defines a method of redirection to the seller’s web page (Optional, default: 2)
            return_url: The url of the return button
            cancel_url: The url of the cancel button