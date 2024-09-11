<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\HubspotAccount;
use App\Models\HubspotContact;

class HubspotContactController extends Controller
{
    // List all contacts for the authenticated user (from DB)
    public function index()
    {
    }
    protected function refreshAccessToken(HubspotAccount $hubspotAccount)
{
    $client_id = env('HUBSPOT_CLIENT_ID');
    $client_secret = env('HUBSPOT_CLIENT_SECRET');

    $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $hubspotAccount->refresh_token,
    ]);

    if ($response->successful()) {
        $tokens = $response->json();
        
        // Update the hubspot_account with new tokens
        $hubspotAccount->update([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        ]);

        return $tokens['access_token'];
    }

    return null;
}


    // Import contacts from HubSpot
    public function import(Request $request)
    {
        $hubspotAccount = HubspotAccount::where('user_id', auth()->user()->id)->first();
    
        if (!$hubspotAccount) {
            return response()->json(['error' => 'No HubSpot account connected.'], 400);
        }
        $hubspotAccount = HubspotAccount::where('user_id', auth()->user()->id)->first();
    
        if (!$hubspotAccount) {
            return response()->json(['error' => 'No HubSpot account connected.'], 400);
        }
        if ($hubspotAccount->token_expires_at <= now()) {
            $newAccessToken = $this->refreshAccessToken($hubspotAccount);
            if ($newAccessToken) {
                $accessToken = $newAccessToken;
            } else {
                return response()->json(['error' => 'Failed to refresh access token.'], 500);
            }
        } else {
            $accessToken = $hubspotAccount->access_token;
        }

        $response = Http::withToken( $accessToken)
            ->get('https://api.hubapi.com/crm/v3/objects/contacts');
    
            \Log::debug($response->json());
        if ($response->successful()) {
            $hubspotContacts = $response->json()['results'];
    
            foreach ($hubspotContacts as $contact) {
                HubspotContact::updateOrCreate(
                    ['hubspot_contact_id' => $contact['id']],
                    [
                        'hubspot_account_id' => $hubspotAccount->id,
                        'first_name' => $contact['properties']['firstname'] ?? '',
                        'last_name' => $contact['properties']['lastname'] ?? '',
                        'email' => $contact['properties']['email'] ?? '',
                        'properties' => json_encode($contact['properties']),
                    ]
                );
            }
    
            return response()->json(['message' => 'Contacts imported successfully.']);
        }
    
        return response()->json(['error' => 'Failed to fetch contacts from HubSpot.'], 500);
    }
    
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
        ]);
    
        $hubspotAccount = HubspotAccount::where('user_id', auth()->user()->id)->first();
    
        if (!$hubspotAccount) {
            return response()->json(['error' => 'No HubSpot account connected.'], 400);
        }
        if ($hubspotAccount->token_expires_at <= now()) {
            $newAccessToken = $this->refreshAccessToken($hubspotAccount);
            if ($newAccessToken) {
                $accessToken = $newAccessToken;
            } else {
                return response()->json(['error' => 'Failed to refresh access token.'], 500);
            }
        } else {
            $accessToken = $hubspotAccount->access_token;
        }
    
        $response = Http::withToken($accessToken)
            ->post('https://api.hubapi.com/crm/v3/objects/contacts', [
                'properties' => [
                    'firstname' => $request->first_name,
                    'lastname' => $request->last_name,
                    'email' => $request->email,
                ]
            ]);
            $contact = $response->json();
            \Log::debug(   $contact );
        if ($response->successful()) {
            $contact = $response->json();
    
            HubspotContact::create([
                'hubspot_account_id' => $hubspotAccount->id,
                'hubspot_contact_id' => $contact['id'],
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'properties' => json_encode($contact['properties']),
            ]);
    
            return response()->json(['message' => 'Contact added successfully.']);
        }
    
        return response()->json(['error' => 'Failed to create contact.'], 500);
    }
    
    
    // Update a contact in HubSpot and locally
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
        ]);
    
        $hubspotAccount = HubspotAccount::where('user_id', auth()->user()->id)->first();
        $contact = HubspotContact::where('hubspot_contact_id', $id)->firstOrFail();
    
        if (!$hubspotAccount) {
            return response()->json(['error' => 'No HubSpot account connected.'], 400);
        }
    
        $response = Http::withToken($hubspotAccount->access_token)
            ->patch("https://api.hubapi.com/crm/v3/objects/contacts/{$id}", [
                'properties' => [
                    'firstname' => $request->first_name,
                    'lastname' => $request->last_name,
                    'email' => $request->email,
                ]
            ]);
    
        if ($response->successful()) {
            $contact->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'properties' => json_encode($response->json()['properties']),
            ]);
    
            return response()->json(['message' => 'Contact updated successfully.']);
        }
    
        return response()->json(['error' => 'Failed to update contact.'], 500);
    }
    

    // Delete a contact from HubSpot and locally
    public function destroy($id)
    {
        $hubspotAccount = HubspotAccount::where('user_id', auth()->user()->id)->first();
        $contact = HubspotContact::findOrFail($id);

        // Delete contact from HubSpot
        $response = Http::withToken($hubspotAccount->access_token)
            ->delete("https://api.hubapi.com/crm/v3/objects/contacts/{$contact->hubspot_contact_id}");

        if ($response->successful()) {
            // Delete contact locally
            $contact->delete();

            return redirect()->route('hubspot.contacts.index')->with('success', 'Contact deleted successfully.');
        }

        return redirect()->back()->with('error', 'Failed to delete contact.');
    }
}
