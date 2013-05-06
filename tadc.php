<?php


class tadc {
    private $_conn;
    private $_base_url;
    private $_key;

    public function __construct($tadc_location, $shared_secret)
    {
        $this->_base_url = $tadc_location;
        $this->_key = $shared_secret;
        $this->_conn = new \Curl(array('cache'=>false, 'debug'=>false));
    }

    public function submit_request($params)
    {
        $params['res.key'] = $this->generate_hash($params);
        return $this->_conn->post($this->_base_url . '/request/', $this->generate_query_string($params), array('httpheader'=>array('Accept: application/json')));
    }

    private function generate_hash(array $params)
    {
        $values = array($this->_key);
        $keys = array_keys($params);
        sort($keys);
        foreach($keys as $key)
        {
            $values[] = $params[$key];
        }
        return md5(implode('|',$values));
    }

    private function generate_query_string(array $params)
    {
        $query = array();
        foreach($params as $key=>$val)
        {
            array_push($query, $key . '=' . urlencode($val));
        }
        return implode('&', $query);
    }
}