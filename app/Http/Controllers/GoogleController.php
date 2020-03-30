<?php


namespace App\Http\Controllers;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GoogleController
{
    public function callback(\Illuminate\Http\Request $request){

        if (isset($_GET['code'])) {

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://accounts.google.com/o/oauth2/token', [
                'form_params' => [
                    'client_id' => env('OAUTH_GOOGLE_CLIENT_ID'),
                    'client_secret' => env('OAUTH_GOOGLE_CLIENT_SECRET'),
                    'code' => $request->get('code'),
                    'redirect_uri' => env('OAUTH_GOOGLE_REDIRECT_URI'),
                    'grant_type' => 'authorization_code'
                ]
            ]);
            $response = $response->getBody()->getContents();
            $tokenInfo = json_decode($response, true);

            if (isset($tokenInfo['access_token'])) {
                $params['access_token'] = $tokenInfo['access_token'];
                $userInfo = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo' . '?' . urldecode(http_build_query($params))), true);
            }
            $email = $userInfo['email'];

            $user = \App\User::where('email', '=', $email)->get()->first();
            if (!$user) {
                $user = new \App\User();
                $user->name = $userInfo['name'];
                $user->email = $email;
                $user->email_verified_at = now();
                $user->password = Hash::make(\Illuminate\Support\Str::random(10));
                $user->remember_token = \Illuminate\Support\Str::random(10);
                $user->save();
            }

            Auth::login($user, true);
            return redirect()->route('home');

        }
    }
}

