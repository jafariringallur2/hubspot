<?php

namespace App\Modules\HubSpot\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HubSpot\Services\HubspotContactService;
use Illuminate\Http\Request;

class HubspotContactController extends Controller
{
    protected $hubSpotContactService;

    public function __construct(HubspotContactService $hubSpotContactService)
    {
        $this->hubSpotContactService = $hubSpotContactService;
    }

    public function index($id){
        
        $contacts = $this->hubSpotContactService->getContacts($id);
        if(!$contacts){
            return abort(404);
        }

        return view('hubspot.contacts.index', ['contacts'=> $contacts, 'accountId' => $id]);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'accountId' => 'required|integer',
        ]);
        $accountId = $validatedData['accountId'];
        $import_response = $this->hubSpotContactService->importContacts($accountId);
        if(!$import_response){
            return response()->json(['error' => 'Failed to fetch contacts from HubSpot.'], 500);
        }
        return response()->json(['message' => 'Contacts imported successfully.']);
    }
}