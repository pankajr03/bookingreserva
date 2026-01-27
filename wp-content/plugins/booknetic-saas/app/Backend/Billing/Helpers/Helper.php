<?php

namespace BookneticSaaS\Backend\Billing\Helpers;

use BookneticSaaS\Models\Tenant;

class Helper
{
    /**
     * @throws \Exception
     */
    public static function gatewayUnsubscribe($gateway, $agreementId)
    {
        $result = $gateway->cancelSubscription($agreementId);

        if (! $result[ 'status' ]) {
            throw new \Exception($result[ 'error' ]);
        }

        Tenant::unsubscribed($agreementId);
    }
}
