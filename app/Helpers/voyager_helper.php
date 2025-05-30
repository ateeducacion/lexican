<?php

    function getQueryParams()
    {
        $query  = explode('&', $_SERVER['QUERY_STRING']);
        $params = array();
        if (sizeof($query) > 1) {                                
            foreach($query as $key => $param)
            {                                        
                if ($param == '') continue;    
                list($name, $value) = explode('=', $param);     
                if (isset($params[urldecode($name)])) {
                    $params[urldecode($name).$key] = urldecode($value);
                } else {
                    $params[urldecode($name)] = urldecode($value);
                }                                                                       
            }
        }
        return $params;
    }

?>