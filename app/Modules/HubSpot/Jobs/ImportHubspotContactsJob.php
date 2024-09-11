<?php

namespace App\Modules\HubSpot\Jobs;

use App\Models\HubspotContact;
use App\Modules\HubSpot\Classes\HubSpotApiConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportHubspotContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $accountId;
    /**
     * Create a new job instance.
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
      $apiConnector = new HubSpotApiConnector();
      $contacts = $apiConnector->getContacts($this->accountId);
      if(isset($contacts['results'])){
        foreach ($contacts['results'] as $contact) {
            HubspotContact::updateOrCreate(
                ['hubspot_contact_id' => $contact['id']],
                [
                    'hubspot_account_id' => $this->accountId,
                    'first_name' => $contact['properties']['firstname'] ?? '',
                    'last_name' => $contact['properties']['lastname'] ?? '',
                    'email' => $contact['properties']['email'] ?? '',
                    'properties' => json_encode($contact['properties']),
                ]
            );
        }
      }
    }
}
