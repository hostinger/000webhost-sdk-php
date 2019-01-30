<?php

class WebhostApi
{

    private $client;
    private $options = [];

    private $ip;
    private $host = 'www.000webhost.com';
    private $language = 'en-us';

    /**
     * RestApi constructor.
     * @param array $credentials
     */
    public function __construct(array $credentials)
    {
        $this->client = new \GuzzleHttp\Client();

        $this->options = [
            'base_uri' => $credentials['api_url'],
            'http_errors' => false,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [
                $credentials['username'],
                $credentials['password'],
            ],
            'json' => [
                'client_id' => $credentials['oauth_client_id'],
                'client_secret' => $credentials['oauth_client_secret'],
            ],
        ];
    }

    /**
     * Sets the client IP
     * @param $ip
     * @return $this
     */
    public function setClientIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Sends the origin host
     * @param $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Sets the origin language
     * @param $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Add custom fields that will be merged to each API call
     * @param $values
     */
    public function addCustomFields($values)
    {
        $this->options = array_merge_recursive($this->options, [
            'json' => $values,
        ]);
    }

    /**
     * Returns given parameters merged with custom values
     * @param $json
     * @param array $additionalOptions
     * @return array
     */
    public function getRequestOptions($json, $additionalOptions = [])
    {
        return array_merge_recursive(array_merge_recursive($this->options, $additionalOptions), [
            'json' => array_merge([
                'host' => $this->host,
                'language' => $this->language,
            ], $json),
            'headers' => [
                'X-Forwarded-For' => $this->ip != null ? $this->ip : $_SERVER['REMOTE_ADDR'],
            ],
        ]);
    }

    /**
     * Logs in the user
     * @param $email
     * @param $password
     * @param $fingerprint
     * @param $impersonationToken
     * @return array
     */
    public function userLogin($email, $password, $fingerprint, $impersonationToken = null)
    {
        $response = $this->client->post('v1/oauth/access_token', $this->getRequestOptions([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'fingerprint' => $fingerprint,
            'impersonation_token' => $impersonationToken,
        ]));

        return $this->transform($response);
    }

    /**
     * Logs in the user with social login
     * @param $email
     * @param $identifier
     * @return array
     */
    public function userLoginSocial($email, $identifier, $impersonationToken = null)
    {
        $response = $this->client->post('v1/oauth/access_token', $this->getRequestOptions([
            'grant_type' => 'social',
            'username' => $email,
            'password' => $identifier,
            'impersonation_token' => $impersonationToken,
        ]));

        return $this->transform($response);
    }

    /**
     * Logs in the user by using a key
     * @param $key
     * @return array
     */
    public function userLoginByKey($key)
    {
        $response = $this->client->post('v1/oauth/access_token/key', $this->getRequestOptions([
            'key' => $key,
        ]));

        return $this->transform($response);
    }

    /**
     * Signs up the user
     * @param $email
     * @param $password
     * @param $appName
     * @return array
     */
    public function userSignup($email, $password, $appName)
    {
        $response = $this->client->post('v1/users', $this->getRequestOptions([
            'email' => $email,
            'password' => $password,
            'app_name' => $appName,
        ]));

        return $this->transform($response);
    }

    /**
     * Signs up the user with social login
     * @param $email
     * @param $identifier
     * @param $provider
     * @return array
     */
    public function userSignupSocial($email, $identifier, $provider)
    {
        $response = $this->client->post('v1/users', $this->getRequestOptions([
            'email' => $email,
            'password' => $identifier,
            'social_login' => $provider,
        ]));

        return $this->transform($response);
    }

    /**
     * Creates a password reset token for the given email
     * @param $email
     * @return array
     */
    public function createUserPasswordResetToken($email)
    {
        $response = $this->client->post('v1/users/password-reset', $this->getRequestOptions([
            'email' => $email,
        ]));

        return $this->transform($response);
    }

    /**
     * Retrieves user ID by a password reset token
     * @param $token
     * @return array
     */
    public function getUserIdByPasswordResetToken($token)
    {
        $response = $this->client->get('v1/user/password-reset/' . $token);

        return $this->transform($response);
    }

    /**
     * Sends a 000webhost recommendation email to visitor's friends
     * @param $name
     * @param $email
     * @param $to
     * @return array
     */
    public function recommend($name, $email, $to)
    {
        $response = $this->client->post('v1/mail/recommend', $this->getRequestOptions([
            'name' => $name,
            'email' => $email,
            'to' => $to,
        ]));

        return $this->transform($response);
    }

    /**
     * Sends an abuse report email
     * @param $url
     * @param $name
     * @param $email
     * @param $message
     * @return array
     */
    public function reportAbuse($url, $name, $email, $message)
    {
        $response = $this->client->post('v1/mail/report-abuse', $this->getRequestOptions([
            'url' => $url,
            'name' => $name,
            'email' => $email,
            'message' => $message
        ]));

        return $this->transform($response);
    }

    /**
     * Sends a contact email
     * @param $name
     * @param $email
     * @param $message
     * @return array
     */
    public function contact($name, $email, $message)
    {
        $response = $this->client->post('v1/mail/contact', $this->getRequestOptions([
            'name' => $name,
            'email' => $email,
            'message' => $message,
        ]));

        return $this->transform($response);
    }

    /**
     * Suspends Abuser App
     * @param $vhost
     * @param $suspender
     * @param $reason
     * @param int $weight
     * @param null $metadata
     * @return array
     */
    public function suspendApp($vhost, $suspender, $reason, $weight = 10, $metadata = null)
    {
        $response = $this->client->post('v1/apps/sleep', $this->getRequestOptions([
            'query' => $vhost,
            'username' => $suspender,
            'reason' => $reason,
            'weight' => $weight,
            'metadata' => $metadata
        ]));

        return $this->transform($response);
    }

    /**
     * Suspends Abuse
     * @param $vhost
     * @param $suspender
     * @param $reason
     * @param int $weight
     * @param null $metadata
     * @return array
     */
    public function suspendUser($vhost, $suspender, $reason, $weight = 10, $metadata = null)
    {
        $response = $this->client->post('v1/users/abuse', $this->getRequestOptions([
            'query' => $vhost,
            'username' => $suspender,
            'reason' => $reason,
            'weight' => $weight,
            'metadata' => $metadata
        ]));

        return $this->transform($response);
    }

    /**
     * Get app by domain
     * @param $domain
     * @return array
     */
    public function getAppByDomain($domain)
    {

        $response = $this->client->get('v1/app/vhost', $this->getRequestOptions([
            'domain' => $domain,
        ]));

        return $this->transform($response);
    }
    
    /**
     * Get apps by user id
     * @param $userId
     * @return array
     */
    public function getAppsByUserId($userId)
    {

        $response = $this->client->get('v1/user/apps/'.$userId, $this->getRequestOptions([]));

        return $this->transform($response);
    }

    /**
     * Transforms the output
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    public function transform(\Psr\Http\Message\ResponseInterface $response)
    {
        return [
            'data' => json_decode($response->getBody()->getContents(), 1),
            'code' => $response->getStatusCode(),
        ];
    }

}
