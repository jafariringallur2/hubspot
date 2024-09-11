<?php

namespace App\Http\Controllers;

use App\Models\HubspotAccount;
use App\Models\HubspotContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HubSpotController extends Controller
{
    // Step 1: Redirect to HubSpot for OAuth Authentication
    public function authenticate(Request $request)
    {
        $client_id = env('HUBSPOT_CLIENT_ID');
        $redirect_uri = 'http://localhost:7847/hubspot/callback';

        $scope = 'oauth crm.objects.contacts.read crm.objects.contacts.write';

        $query = http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => $scope,
            'response_type' => 'code',
        ]);
    
        return redirect('https://app.hubspot.com/oauth/authorize?' . $query);
    }

    // Step 2: Handle OAuth Callback
    public function callback(Request $request)
    {
        $client_id = env('HUBSPOT_CLIENT_ID');
        $client_secret = env('HUBSPOT_CLIENT_SECRET');
        $redirect_uri = 'http://localhost:7847/hubspot/callback';

        // Exchange code for tokens
        $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $request->code,
        ]);

        $tokens = $response->json();
        if (!isset($tokens['access_token'])) {
            return redirect()->route('dashboard')->with('error', 'Error connecting to HubSpot.');
        }
    
        $accessToken = $tokens['access_token'];
        \Log::debug( $accessToken);
    
        // Make an API request to get the account details (including the hub_id)
        $accountDetailsResponse = Http::withToken($accessToken)
            ->get('https://api.hubapi.com/oauth/v1/access-tokens/' . $accessToken);
    
        $accountDetails = $accountDetailsResponse->json();
        \Log::debug( $accountDetails);
        if (!isset($accountDetails['hub_id'])) {
            return redirect()->route('dashboard')->with('error', 'Unable to retrieve HubSpot account details.');
        }
    
        $hub_id = $accountDetails['hub_id'];
    
        // Save tokens and account details in the DB
        $hubspotAccount = HubspotAccount::updateOrCreate([
            'hubspot_account_id' => $hub_id
        ], [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
            'user_id' => auth()->user()->id,
        ]);
    
        return redirect()->route('dashboard')->with('success', 'HubSpot account connected successfully!');
    }

    // Step 3: List Connected HubSpot Accounts
    public function listAccounts(Request $request)
    {
        // Get all connected accounts for the authenticated user
        $hubspotAccounts = HubspotAccount::where('user_id', auth()->user()->id)->get();

        return response()->json($hubspotAccounts);
    }

    // Step 4: View a specific HubSpot Account (optional)
    public function viewAccount($id)
    {
      
        $hubspotAccount = HubspotAccount::where('id',$id)->where('user_id', auth()->user()->id)->first();

        if (!$hubspotAccount) {
            return redirect()->back()->with('error', 'No HubSpot account connected.');
        }

        // Get all contacts linked to the HubSpot account
        $contacts = HubspotContact::where('hubspot_account_id', $hubspotAccount->id)->get();

        return view('hubspot.contacts.index', compact('contacts'));
    }
}
