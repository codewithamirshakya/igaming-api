<?php

namespace App\Helpers;

trait Utility 
{

    public function clientIP() 
    {      
        $ip = $_SERVER['REMOTE_ADDR'] ?? gethostbyname(gethostname());

        $ips = [
                '127.0.0.1',
                '::1',
                '103.200.210.26', // sg
                '103.203.48.106', // hk
                '188.40.36.132'
            ]; // eu;

        if (in_array($ip, $ips)) {
            $ip =   $_SERVER['HTTP_X_REAL_IP'] ??
            $_SERVER['HTTP_X_FORWARDED_FOR'] ??
            $ip;
        } else {
            $ip =   $_SERVER['HTTP_CLIENT_IP'] ??
                    $_SERVER['HTTP_X_FORWARDED_FOR'] ??
                    $_SERVER['HTTP_X_FORWARDED'] ??
                    $_SERVER['HTTP_FORWARDED_FOR'] ??
                    $_SERVER['HTTP_FORWARDED'] ??
                    $ip;
        }
            
        return $ip;
    }
}