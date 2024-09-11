<?php

namespace App\Modules\HubSpot\Services;

use App\Modules\HubSpot\Classes\HubSpotApiConnector;
use App\Modules\HubSpot\Jobs\ImportHubspotContactsJob;
use App\Modules\HubSpot\Repositories\HubspotAccountRepository;
use App\Modules\HubSpot\Repositories\HubspotContactRepository;

class HubspotContactService
{
    protected $hubSpotContactRepository;
    protected $hubSpotAccountRepository;
    protected $hubSpotApiConnector;

    public function __construct()
    {
        $this->hubSpotContactRepository = new HubspotContactRepository();
        $this->hubSpotAccountRepository = new HubspotAccountRepository();
        $this->hubSpotApiConnector = new HubSpotApiConnector();
    }

    private function getUserId(){
        return auth()->user()->id ?? NULL;
    }

    public function getContacts($accountId){
        $hubspotAccount = $this->hubSpotAccountRepository->getAccount($accountId,$this->getUserId());

        if (!$hubspotAccount) {
            return false;
        }
        return $this->hubSpotContactRepository->getContacts($accountId);
    }

    public function importContacts($accountId){
        $hubspotAccount = $this->hubSpotAccountRepository->getAccount($accountId,$this->getUserId());

        if (!$hubspotAccount) {
            return false;
        }
        ImportHubspotContactsJob::dispatch($accountId);
        return true;

    }
}