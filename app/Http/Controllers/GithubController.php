<?php


namespace App\Http\Controllers;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GithubController
{
    public function callback(\Illuminate\Http\Request $request)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://github.com/login/oauth/access_token', [
            'form_params' => [
                'client_id' => env('OAUTH_GITHUB_CLIENT_ID'),
                'client_secret' => env('OAUTH_GITHUB_CLIENT_SECRET'),
                'code' => $request->get('code'),
                'redirect_uri' => env('OAUTH_GITHUB_REDIRECT_URI'),
            ]
        ]);
//    dd($response->getBody()->getContents());
        $accessToken = $response->getBody()->getContents();

//    echo $accessToken.'<br>';
        parse_str($accessToken, $result);
//    dd($result['access_token']);

        $response = $client->request('GET', 'https://api.github.com/user', [
            'headers' => [
                'Authorization' => 'token ' . $result['access_token'],
            ]
        ]);
        $userInfo = json_decode($response->getBody()->getContents(), true);

        $response = $client->request('GET', 'https://api.github.com/user/emails', [
            'headers' => [
                'Authorization' => 'token ' . $result['access_token'],
            ]
        ]);
        $userEmails = json_decode($response->getBody()->getContents(), true);

        $email = null;
        foreach($userEmails as $userEmail) {
            if($userEmail['primary'] === true) {
                $email = $userEmail['email'];
                break;
            }
        }

        $user = \App\User::where('email', '=', $email)->get()->first();
        if(!$user) {
            $user = new \App\User;
            $user->name = $userInfo['name'];
            $user->email = $email;
            $user->email_verified_at = now();
            $user->password = \Illuminate\Support\Facades\Hash::make(Illuminate\Support\Str::random(10));
            $user->remember_token = Illuminate\Support\Str::random(10);
            $user->save();
        }

        \Illuminate\Support\Facades\Auth::login($user, true);
        return redirect()->route('home');
    }

}
