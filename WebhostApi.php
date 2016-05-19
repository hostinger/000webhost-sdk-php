<?php

class WebhostApi
{
    protected $username = '';
    protected $password = '';
    protected $api_url = '';

    /**
     * $config['username'] string
     * $config['password'] string
     * $config['api_url']  string Must end with '/'
     *
     * @param array $config (See above)
     */
    public function __construct($config)
    {
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->api_url = $config['api_url'];
    }

    /**
     * @param $domain
     * @param $review
     * @param $name
     * @param $email
     * @param $rating
     * @return array
     * @throws WebhostApiException
     */
    public function reviewCreate($domain, $review, $name, $email, $rating){
        $params = [
            'review'    => $review,
            'name'      => $name,
            'email'     => $email,
            'rating'    => $rating
        ];

        return $this->make_call('/content/review/'.$domain, 'POST', $params);
    }

    /**
     * @param $name
     * @param $emailFrom
     * @param $emailsTo
     * @return array
     * @throws WebhostApiException
     */
    public function recommend($name, $emailFrom, $emailsTo){
        $params = [
            'name' => $name,
            'email' => $emailFrom,
            'to' => $emailsTo
        ];

        return $this->make_call('/content/recommend', 'POST', $params);
    }

    /**
     * @param $url
     * @param $name
     * @param $email
     * @param $message
     * @return array
     * @throws WebhostApiException
     */
    public function reportAbuse($url, $name, $email, $message){
        $params = [
            'url' => $url,
            'name' => $name,
            'email' => $email,
            'message' => $message
        ];

        return $this->make_call('/content/report-abuse', 'POST', $params);
    }

    /**
     * @param $email
     * @param $password
     * @return array
     * @throws WebhostApiException
     */
    public function userLogin($email, $password){
        $params = [
            'email' => $email,
            'password' => $password
        ];
        return $this->make_call('/user/login', 'POST', $params);
    }

    /**
     * @param $email
     * @param $password
     * @return array
     * @throws WebhostApiException
     */
    public function affiliateLogin($email, $password){
        $params = [
            'email' => $email,
            'password' => $password
        ];
        return $this->make_call('/affiliate/login', 'POST', $params);
    }

    /**
     * @param $name
     * @param $email
     * @param $password
     * @param $domainType
     * @param $domain
     * @param $subdomain
     * @return array
     * @throws WebhostApiException
     */
    public function userSignup($name, $email, $password, $domainType, $domain, $subdomain){
        $params = [
            'name'          => $name,
            'email'         => $email,
            'password'      => $password,
            'domain_type'   => $domainType,
            'domain'        => $domain,
            'subdomain'     => $subdomain,
        ];
        return $this->make_call('/user/signup','POST',$params);
    }

    /**
     * @param $name
     * @param $email
     * @param $password
     * @return array
     * @throws WebhostApiException
     */
    public function affiliateSignup($name,$email,$password){
        $params = [
            'name'                 => $name,
            'email'                => $email,
            'password'             => $password,
        ];
        return $this->make_call('/affiliate/signup','POST',$params);
    }

    /**
     * @return string
     */
    private function getIp()
    {
        $address = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
        if (is_string($address)) {
            if (strpos($address, ',') !== false) {
                $address = end(explode(',', $address));
            }
        }
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }
        return $address;
    }

    /**
     * @param string $cmd
     * @param string $method
     * @param array $post_fields
     * @return array
     * @throws WebhostApiException
     */
    private function make_call($cmd, $method = 'GET', $post_fields = array())
    {
        $result = $this->get_url($this->api_url.$cmd, $method, $post_fields, $this->username, $this->password);
        $result['data'] = json_decode($result['data'],1);
        return $result;
    }

    /**
     * @param array $result
     * @return array
     */
    public function getValidationErrorsForResult($result) {
        if(isset($result['validation']) && !empty($result['validation'])) {
            return $result['validation'];
        }
        return array();
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $post_fields
     * @param string $user
     * @param string $password
     * @param int $timeout
     * @return array
     * @throws WebhostApiException
     */
    private function get_url($url, $method, $post_fields = array(), $user = null, $password = null, $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        if ($user && $password) {
            curl_setopt($ch, CURLOPT_USERPWD, "$user:$password");
        }

        switch (strtolower($method)) {
            case'delete' :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case 'post' :
                $fields = http_build_query($post_fields, null, '&');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                break;
            case 'get' :
                break;
        }

        $data = curl_exec($ch);
        if ($data === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new WebhostApiException("Request error: " . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        switch($httpCode){
            case 401:
                throw new WebhostApiException("API authentication failed. HTTP status code: 401");
            case 500:
                throw new WebhostApiException("API endpoint encountered an error. HTTP status code: 500");
        }

        return [
            'data'  => $data,
            'code'  => $httpCode
        ];
    }
}
