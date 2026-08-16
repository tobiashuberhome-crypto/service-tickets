<?php

namespace App\Http\Controllers;

use App\Models\CustomerPortalAccount;

class CibenaCustomerPortalController extends GeiserCustomerPortalController
{
    protected const CUSTOMER_ID = 10;
    protected const SESSION_KEY = 'cibena_customer_portal_account_id';
    protected const PORTAL_SCOPE = CustomerPortalAccount::PORTAL_SCOPE_CIBENA;
    protected const PORTAL_ROUTE_PREFIX = 'cibena-portal';
    protected const VIEW_PREFIX = 'customer-portal-cibena';
    protected const PORTAL_NAME = 'Cibena-Serviceportal';

    /**
     * @return array<int>
     */
    protected function customerIdsForPortal(CustomerPortalAccount $account): array
    {
        return array_values(array_unique([
            9,
            10,
            (int) $account->dolibarr_thirdparty_id,
        ]));
    }
}
