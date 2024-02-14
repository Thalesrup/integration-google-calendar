<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\User;

class IntegrationController extends Controller
{
    /**
     * Manipula a solicitação de callback.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {
        // Lógica para lidar com a solicitação de callback
        return response()->json(['message' => 'Callback received'], 200);
    }

    /**
     * Gets a google client
     *
     * @return \Google_Client
     * INCOMPLETE
     */
    private function getClient(): \Google_Client
    {
        $configJson = base_path() . '/config.json';

        $applicationName = 'Google Agenda X Operand';

        $client = new \Google_Client();
        $client->setApplicationName($applicationName);
        $client->setAuthConfig($configJson);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        $client->setScopes(
            [
                \Google\Service\Oauth2::USERINFO_PROFILE,
                \Google\Service\Oauth2::USERINFO_EMAIL,
                \Google\Service\Oauth2::OPENID,
                \Google\Service\Calendar::CALENDAR,
                \Google\Service\Calendar::CALENDAR_EVENTS,
            ]
        );
        $client->setIncludeGrantedScopes(true);
        return $client;
    }

    /**
     * Return the url of the google auth.
     * FE should call this and then direct to this url.
     *
     * @return JsonResponse
     * INCOMPLETE
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        return response()->json($authUrl, 200);
    }

    /**
     * Login and register
     * Gets registration data by calling google Oauth2 service
     *
     * @return JsonResponse
     */
    public function loginGoogle(Request $request): JsonResponse
    {
        $authCode = urldecode($request->input('auth_code'));
        $client = $this->getClient();
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        $client->setAccessToken(json_encode($accessToken));

        $service = new \Google\Service\Oauth2($client);
        $userFromGoogle = $service->userinfo->get();

        $user = User::where('provider_name', '=', 'google')
            ->where('provider_id', '=', $userFromGoogle->id)
            ->first();

        $this->createOrUpdate($user, $userFromGoogle, $accessToken);

        $token = $user->createToken("Google")->accessToken;
        return response()->json($token, 201);
    }

    public function createOrUpdate(User $user, $userFromGoogle,array $accessToken)
    {
        if (!$user) {
            $user = User::create([
                'provider_id' => $userFromGoogle->id,
                'provider_name' => 'google',
                'google_access_token_json' => json_encode($accessToken),
                'name' => $userFromGoogle->name,
                'email' => $userFromGoogle->email,
                //'avatar' => $providerUser->picture, // in case you have an avatar and want to use google's
            ]);
        } else {
            $user->google_access_token_json = json_encode($accessToken);
            $user->save();
        }
    }

    /**
     * Returns a google client that use email
     *
     * @return \Google_Client
     */
    private function getUserClient(string $email): \Google_Client
    {
        $user = User::where('email', '=', $email)->first();
        if(!$user) {
            return response()->json(
                [
                    'success' => false, 'msg' => 'inform email don\'t exist!'
                ], 422
            );
        }

        $accessTokenJson = stripslashes($user->google_access_token_json);

        $client = $this->getClient();
        $client->setAccessToken($accessTokenJson);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $client->setAccessToken($client->getAccessToken());

            $user->google_access_token_json = json_encode($client->getAccessToken());
            $user->save();
        }

        return $client;
    }

    /**
     * Get meta data on a page of files in user's google drive
     *
     * @return JsonResponse
     */
    public function listAll($email):JsonResponse
    {
        $client = $this->getUserClient($email);
        $service = new \Google\Service\Calendar($client);

        $calendarList  = $service->calendarList->listCalendarList();

        return response()->json($calendarList->getItems(), 200);
    }

}
