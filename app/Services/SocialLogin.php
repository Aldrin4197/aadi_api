<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SocialLogin
{
    public function enabled(string $provider): bool
    {
        if (!in_array($provider, ['google', 'facebook'], true)) return false;
        $config = config("social_login.$provider");
        return !empty($config['client_id']) && !empty($config['client_secret']) &&
            !empty($config['redirect_uri']) && ($provider !== 'facebook' || preg_match('/^v\d+\.\d+$/', $config['version'] ?? ''));
    }

    public function providers(): array
    {
        return array_values(array_filter(['google', 'facebook'], fn ($provider) => $this->enabled($provider)));
    }

    public function authorizeUrl(string $provider, string $state, string $verifier): string
    {
        abort_unless($this->enabled($provider), 404);
        $config = config("social_login.$provider");
        $parameters = ['client_id'=>$config['client_id'], 'redirect_uri'=>$config['redirect_uri'], 'response_type'=>'code', 'state'=>$state];
        if ($provider === 'google') {
            $parameters += ['scope'=>'openid email profile', 'code_challenge'=>rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='), 'code_challenge_method'=>'S256'];
            $url = 'https://accounts.google.com/o/oauth2/v2/auth';
        } else {
            $parameters += ['scope'=>'email,public_profile'];
            $url = "https://www.facebook.com/{$config['version']}/dialog/oauth";
        }
        return $url.'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    public function identity(string $provider, string $code, string $verifier): array
    {
        $config = config("social_login.$provider");
        $parameters = ['client_id'=>$config['client_id'], 'client_secret'=>$config['client_secret'], 'redirect_uri'=>$config['redirect_uri'], 'code'=>$code];
        if ($provider === 'google') {
            $token = Http::asForm()->acceptJson()->timeout(10)->post('https://oauth2.googleapis.com/token', $parameters + ['grant_type'=>'authorization_code', 'code_verifier'=>$verifier])->throw()->json('access_token');
            throw_unless(is_string($token) && $token !== '', \UnexpectedValueException::class);
            $profile = Http::withToken($token)->acceptJson()->timeout(10)->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
            $id = $profile['sub'] ?? null;
            // Only Google-authoritative verified addresses bypass Aadi email verification.
            $verified = ($profile['email_verified'] ?? false) === true &&
                (str_ends_with(strtolower($profile['email'] ?? ''), '@gmail.com') || !empty($profile['hd']));
        } else {
            $base = "https://graph.facebook.com/{$config['version']}";
            $token = Http::asForm()->acceptJson()->timeout(10)->post($base.'/oauth/access_token', $parameters)->throw()->json('access_token');
            throw_unless(is_string($token) && $token !== '', \UnexpectedValueException::class);
            $profile = Http::withToken($token)->acceptJson()->timeout(10)->get($base.'/me', ['fields'=>'id,name,email', 'appsecret_proof'=>hash_hmac('sha256', $token, $config['client_secret'])])->throw()->json();
            $id = $profile['id'] ?? null;
            $verified = false;
        }
        if (!is_string($id) || $id === '' || strlen($id) > 255) throw new \UnexpectedValueException('Missing provider identity.');
        return ['id'=>$id, 'email'=>strtolower(trim($profile['email'] ?? '')), 'name'=>mb_substr($profile['name'] ?? 'Aadi user', 0, 255), 'verified'=>$verified];
    }
}
