<?php

namespace Spider\Library;

final class StringUtil
{
    /**
     * 转换&#和&#x编码格式的utf-8到正常格式
     */
    public static function utf2utf(&$string)
    {
        $strarr = explode('\n', $string);
        $string = '';
        foreach ($strarr as $str) {
            preg_match_all("/(?:%u.{4})|&#x.{4};|&#\d+;|.+/U", $str, $result);
            $array = $result[0];
            foreach ($array as $k => $v) {
                if (substr($v, 0, 2) == "%u") {
                    $array[$k] = mb_convert_encoding(pack("H4", substr($v, -4)), "UTF-8", "UCS-2");
                } elseif (substr($v, 0, 3) == "&#x") {
                    $array[$k] = mb_convert_encoding(pack("H4", substr($v, 3, -1)), "UTF-8", "UCS-2");
                } elseif (substr($v, 0, 2) == "&#") {
                    $array[$k] = mb_convert_encoding(pack("n", substr($v, 2, -1)), "UTF-8", "UCS-2");
                }
            }
            $string .= self::utf2asc(join("", $array)) . "\n";
        }
    }

    /**
     * 根据开始标签和紧随其后的标签，提取其中间文本
     * @param string $str 原始文本
     * × @param string $substr 处理后的文本
     * @param string $start_str 开头字符串
     * @param string $end_str 结尾字符串
     * @param boolean $include 是否包含头尾字符串
     * @return boolean 提取是否成功
     */
    public static function parse_substr(&$str, &$substr, $start_str, $end_str, $include = true)
    {
        $substr = '';
        $start = strpos($str, $start_str);
        if ($start === false) {
            return false;
        }
        $end = strpos($str, $end_str, $start + strlen($start_str));
        if ($end === false) {
            return false;
        }
        if (!$include) {
            $start += strlen($start_str);
        } else {
            $end += strlen($end_str);
        }
        $substr = substr($str, $start, $end - $start);
        $substr = trim(str_replace("\r\n", "", $substr));

        return true;
    }

    /**
     * 去掉文本中的所有标签
     * @param string $txt 待处理字符串
     * @param boolean $onlyTags 是否仅去掉标签
     */
    public static function trimTag(&$txt, $onlyTags=true)
    {
        if (!$onlyTags) {
            $txt = preg_replace("/<[^<>]*?>.*?<\/[^<>]*?>/", "", $txt);
            $txt = preg_replace("/<.*?>/", "", $txt);
        } else {
            $txt = preg_replace("/<[^<>]+?>/", "", $txt);
        }
        $txt = trim($txt);
    }

    /*
     * 只对url中汉字部分进行urlencode，主要是为了让curl能够处理汉字url
     */
    public static function urlencode_hanzi($str)
    {
        $thisstr = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (ord($str[$i]) >= 128) {
                $thisstr .= urlencode($str[$i] . $str[$i + 1] . $str[$i + 2]);
                $i += 2;
            } elseif ($str[$i] == ' ') {
                $thisstr .= '%20';
            } else {
                $thisstr .= $str[$i];
            }
        }
        return $thisstr;
    }

    /**
     * 根据父url和相对url确定绝对url
     * @param string $parent
     * @param string $current
     * @return false|string
     */
    public static function createAbsoluteUrl($parent, $current)
    {
        if (strlen($current) == 0) {
            return false;
        }
        if (strpos($current, 'http://') === 0) {
            return $current;
        }
        $arr = parse_url($parent);
        $port = empty($arr['port']) ? '' : ':'.$arr['port'];
        $thisurl = 'http://' . $arr['host'] . $port;

        if ($current[0] == '/') {
            return $thisurl . $current;
        }
        if (!isset($arr['path'])) {
            $arr['path'] = '/';
        }
        $pos = strrpos($arr['path'], '/');
        if ($pos === false) {
            return false;
        }
        $path = substr($arr['path'], 0, $pos+1);
        if ($current[0] != '.') {
            return $thisurl . $path . $current;
        }
        if (strpos($current, './') === 0) {
            return $thisurl . $path . substr($current, 2);
        }
        $tmpcurrent = $current;
        $tmppath = $path;
        while (strpos($tmpcurrent,'../') === 0) {
            $tmpcurrent = substr($tmpcurrent, 3);
            $tmpos = strrpos($tmppath, '/');
            if ($tmpos === 0 && strpos($tmpcurrent,'../') !== false) {
                return false;
            }
            $tmppath = substr($tmppath, 0, $tmpos);
        }
        $tmpos = strrpos($tmppath, '/');
        $path = substr($tmppath, 0, $tmpos+1);
        $current = $tmpcurrent;
        return $thisurl . $path . $current;
    }

    /**
     * &#x[0-9]*;和&#[0-9]*;转换函数
     */
    private static function utf2asc($str)
    {
        //十六进制
        if (preg_match_all("/&#x([0-9a-fA-F]*);/", $str, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $bin = hex2bin($matches[1][$i]);
                $str = str_replace($matches[0][$i], $bin, $str);
            }
        }
        //十进制
        if (preg_match_all("/&#([0-9]*)/", $str, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $bin = chr(decbin($matches[1][$i]));
                $str = str_replace($matches[0][$i], $bin, $str);
            }
        }
        return $str;
    }

    private static function hex2bin($str)
    {
        $bin = '';
        for ($i = 0; $i < strlen($str); $i = $i + 2) {
            $bin .= chr(hexdec(substr($str, $i, 2)));
        }
        return $bin;
    }

}

