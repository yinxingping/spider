<?php

namespace Spider\Library;

use \Requests;
use \Requests_Exception;

class Download
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function getPage($url, $type)
    {
        $logInfo = 'PAGE_' . $type . "_DOWN | " . $url . " | ";

        try {
            $response = Requests::request($url);
        } catch (Requests_Exception $e) {
            $logInfo .= "EXCEPTION | " . $e->getMessage();
            $this->logger->info($logInfo);
            return false;
        }
        if (!empty($response)) {
            $logInfo .= "OK";
            $page = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $response->body);
            $encoding = self::getEncoding($response);
            if ($encoding != 'utf-8') {
                $page = mb_convert_encoding($page, 'UTF-8', $encoding);
            }
            $this->logger->info($logInfo);
            return $page;
        } else {
            $logInfo .= "ERR";
            $this->logger->info($logInfo);
            return false;
        }
    }

    public function getImg($url)
    {
        $logInfo = 'IMG_COVER' . "_DOWN | " . $url . " | ";

        try {
            $response = Requests::request($url);
        } catch (Requests_Exception $e) {
            $logInfo .= "EXCEPTION | " . $e->getMessage();
            $this->logger->info($logInfo);
            return false;
        }
        if (!empty($response)) {
            $logInfo .= "OK";
            if ($response->headers['content-type'] != 'image/jpeg') {
                $logInfo .= "ERR";
                $this->logger->info($logInfo);
                return false;
            }
            $this->logger->info($logInfo);
            return $response->body;
        } else {
            $logInfo .= "ERR";
            $this->logger->info($logInfo);
            return false;
        }
    }

    private static function getEncoding(&$response)
    {
        if (preg_match('/charset=(.+)/i', $response->headers['content-type'], $m) ||
            preg_match('/charset=(.+?)("|\'|>)/i', $response->body, $m)) {
            $encoding = strtolower(trim($m[1]));
        }

        return isset($encoding) ? $encoding : 'gbk';
    }
}

