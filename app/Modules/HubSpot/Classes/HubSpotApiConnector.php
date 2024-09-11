<?php

namespace App\Modules\HubSpot\Classes;

use App\Models\HubspotAccount;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class HubSpotApiConnector
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $base_url;
    protected $accessToken;

    public function __construct()
    {
        $this->clientId = env('HUBSPOT_CLIENT_ID');
        $this->clientSecret = env('HUBSPOT_CLIENT_SECRET');
        $this->redirectUri = 'http://localhost:7847/hubspot/callback';
        $this->base_url = 'https://api.hubapi.com/crm/v3/objects/';
    }


    private function sendRequest($accountId,$method, $uri, array $headers = [], $body = null)
    {
        $this->setAccessToken($accountId);
        if (!$this->accessToken) {
            throw new \Exception('Failed to retrieve access token.');
        }
        $client = new Client(['base_uri' => $this->base_url]);
        try {
            $options = [
                'headers' => $this->getRequestHeaders($headers),
                'timeout' => 300,
            ];
            if ($method !== 'GET' && $body !== null) {
                $options['body'] = $body;
            }
    
            $response = $client->request($method, $uri, $options);
    
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            } else {
                throw new \Exception('Error: ' . $response->getBody());
            }
        } catch (\Exception $e) {
            throw new \Exception('Error connecting to HubSpot: ' . $e->getMessage());
        }
    }

    private function getRequestHeaders(array $headers = []){
        $defaultHeaders = [
            'Authorization' => "Bearer $this->accessToken",
            'Content-Type'  => 'application/json',
        ];
        return array_merge($defaultHeaders, $headers);
    }

    private function setAccessToken($accountId){
        $hubspotAccount = HubspotAccount::where('id',$accountId)->where('user_id', auth()->user()->id)->first();
        
        if (!$hubspotAccount) {
           $this->accessToken = NULL;
           return;
        }

        if ($hubspotAccount->token_expires_at <= now()) {
            $tokenData = $this->refreshAccessToken($hubspotAccount);
            if (isset($tokenData['access_token'])) {
                $hubspotAccount->update([
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]);
                $this->accessToken = $tokenData['access_token'];
            } else{
                $this->accessToken = NULL;
            }
        } else {
            $this->accessToken = $hubspotAccount->access_token;
        }
    }


    private function refreshAccessToken($refreshToken)
    {
        $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        
        return $response->json();
            
    }
    public function getAuthUrl()
    {
        $scope = 'oauth crm.objects.contacts.read crm.objects.contacts.write';
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $scope,
            'response_type' => 'code',
        ]);

        return 'https://app.hubspot.com/oauth/authorize?' . $query;
    }

    /**
     * Used for callback function
     */
    public function getAccessToken($code)
    {
        $response = Http::asForm()->post('https://api.hubapi.com/oauth/v1/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        return $response->json();
    }

    public function getAccountDetails($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->get('https://api.hubapi.com/oauth/v1/access-tokens/' . $accessToken);

        return $response->json();
    }

    public function getContacts($accountId)
    {
        return  $this->sendRequest($accountId,'GET', "contacts");
    }

    public function createContact($accountId,Array $data)
    {
       return  $this->sendRequest($accountId,'POST', "contacts", ['content-type' => 'application/json'], json_encode($data));
    }

    public function updateContact($accountId,$contactId, $data)
    {
        return  $this->sendRequest($accountId,'PATCH', "contacts/$contactId", ['content-type' => 'application/json'], json_encode($data));
    }

    public function deleteContact($accountId,$contactId)
    {
        return  $this->sendRequest($accountId,'DELETE', "contacts/$contactId", ['content-type' => 'application/json'],null);
    }
}
