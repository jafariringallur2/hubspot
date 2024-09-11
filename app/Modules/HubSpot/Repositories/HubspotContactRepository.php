<?php

namespace App\Modules\HubSpot\Repositories;

use App\Models\HubspotContact;

class HubspotContactRepository
{

    public function getContacts($hubspot_account_id)
    {
        return HubspotContact::where('hubspot_account_id', $hubspot_account_id)->get();
    }
}
