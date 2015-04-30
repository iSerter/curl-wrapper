<?php 

namespace Curl;

/*
 * The MIT License
 *
 * Copyright 2014 Ilyas Serter <ilyasserter@gmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A wrapper class for php-curl extension. 
 *
 * @author Ilyas Serter <ilyasserter@gmail.com>
 * @version 1.0
 * @date November 9, 2014
 */
class Curl {
    
    const VERSION = 1.0;
    
    protected $session;
    protected $options;
    protected $headers;
    protected $cookies;
    public $response;
    public $url;
    
    /**
     * 
     * @param type $url
     * @throws \Exception
     */
    function __construct($url = null)
    {
        if(!extension_loaded('curl'))
            throw new \Exception('cURL library is not loaded');
        
        if(!is_null($url))
            $this->start($url);
    }
    
    /**
     * Init curl session and set up default vars
     * @param type $url
     */
    public function start($url) {
        $this->reset();
        $this->url = $url;
        $this->session = curl_init($url);
        $this->userAgent();
        
        //$this->option(CURLINFO_HEADER_OUT, true);
        //$this->option(CURLOPT_HEADER, true);
        $this->option(CURLOPT_RETURNTRANSFER, true);
        $this->option(CURLOPT_FOLLOWLOCATION,true);
        $this->option(CURLOPT_TIMEOUT,30);        
    }
    
    /**
     * Set CURL option. 
     * 
     * @param type $key
     * @param type $value
     * @return \Curl
     */
    public function option($key, $value)
    {
        if (is_string($key) && !is_numeric($key))
            $key = constant('CURLOPT_' . strtoupper($key));
        
        $this->options[$key] = $value;
        return $this;
    }
    
    /**
     * Set http header
     * 
     * @param string $header
     * @param string $content
     * @return \Curl
     */
    public function header($header, $content = NULL)
    {
        $this->headers[] = $content ? $header . ': ' . $content : $header;
        return $this;
    }
    
    /**
     * Set cookie
     * 
     * @param type $key
     * @param type $value
     * @return \Curl
     */
    public function cookie($key,$value)
    {
        $this->cookies[$key] = $value;
        return $this;
    }
    
    /**
     * Set file path for cookie storage
     * @param string $filePath
     */
    public function cookieFile($filePath)
    {
        $this->option(CURLOPT_COOKIEFILE, $filePath);
    }
    
    /**
     * 
     * @param type $jar
     * @return \Curl
     */
    public function cookieJar($jar)
    {
        $this->option(CURLOPT_COOKIEJAR, $jar);
        return $this;
    }
    
    
    /**
     * Set useragent option for curl request
     * @param string $agent
     * @return \Curl
     */
    public function userAgent($agent = 'PHP-Curl-Wrapper 1.0 (+http://www.ilyasserter.com)') {
        
        $this->option(CURLOPT_USERAGENT,$agent);
        return $this;
    }
    
    /**
     * 
     * @param type $referrer
     * @return \Curl
     */
    public function referrer($referrer)
    {
        $this->option(CURLOPT_REFERER, $referrer);
        return $this;
    }
    
    /**
     * 
     * @param type $gateway
     * @return \Curl
     */
    public function proxy($gateway)
    {
        $this->option(CURLOPT_HTTPPROXYTUNNEL, TRUE);
        $this->option(CURLOPT_PROXY, $gateway);
        return $this;
    }
    
    /**
     * 
     * @param type $username
     * @param type $password
     * @return \Curl
     */
    public function proxyAuth($username = '', $password = '')
    {
        $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }
    
    /**
     * 
     * @param type $method
     * @return \Curl
     */
    public function httpMethod($method)
    {
        $this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        return $this;
    }
    
    /**
     * Basic HTTP Authentication 
     * 
     * @param type $username
     * @param type $password
     * @param type $type
     * @return \Curl
     */
    public function httpAuth($username = '', $password = '', $type = 'basic')
    {
        $this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }
    

    /**
     * resets object properties
     */
    public function reset()
    {
        $this->response = '';
        $this->headers = array();
        $this->cookies = array();
        $this->options = array();
        $this->session = NULL;
    }
    
    /**
     * Processes curl options
     */
    protected function prepare() {
        // headers
        if (!empty($this->headers))
            $this->option(CURLOPT_HTTPHEADER, $this->headers);
        // cookies
        if(!empty($this->cookies))
            $this->option(CURLOPT_COOKIE, http_build_query($this->cookies, NULL, '&'));
        // pass options to curl session
        curl_setopt_array($this->session, $this->options);
    }
    
    /**
     * 
     * @return type
     */
    public function get()
    { 
        $this->prepare();
        $this->response = curl_exec($this->session);
        curl_close($this->session);
        return $this->response;
    }
    
    /**
     * 
     * @param array $params
     */
    public function post($params = array(), $json = false)
    {
        if($json)
            $params = json_encode ($params);
        elseif (is_array($params))
            $params = http_build_query($params, NULL, '&');
        
        $this->httpMethod('post');
        $this->option(CURLOPT_POST, TRUE);
        $this->option(CURLOPT_POSTFIELDS, $params);
        $this->prepare();

        $this->response = curl_exec($this->session);
        curl_close($this->session);
        return $this->response;
    }
    
    // request using put method
    public function put($params = array())
    {
        if (is_array($params))
            $params = http_build_query($params, NULL, '&');

        $this->httpMethod('put');
        $this->option(CURLOPT_POSTFIELDS, $params);
        $this->prepare();

        $this->response = curl_exec($this->session);
        curl_close($this->session);
        return $this->response;
    }
        
    // http delete request
    public function delete($params = array()) {
        if (is_array($params))
            $params = http_build_query($params, NULL, '&');

        $this->httpMethod('DELETE');
        $this->option(CURLOPT_POSTFIELDS, $params);
        $this->prepare();

        $this->response = curl_exec($this->session);
        curl_close($this->session);
        return $this->response;
    }
    

}
