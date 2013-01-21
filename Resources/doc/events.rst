Events
======

Introduction
------------
The DotpayBundle dispatches special events for the dotpay workflow.

.. tip ::

    For a list of all available events, you can also take a look at the class
    ``ETS\Payment\DotpayBundle\Event\Events``.

Confirmation Received Event
--------------------------
**Name**: ``payment.dotpay.confirmation_received``

**Event Class**: ``ETS\Payment\DotpayBundle\Event\DotpayConfirmationReceivedEvent``

This event is dispatched directly after the begining of the URLC action called by dotpay.

You have access to the ``PaymentInstruction`` and the request parameter bag.
