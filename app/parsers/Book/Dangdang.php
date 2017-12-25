<?php

namespace Spider\Parser\Book;

use Spider\StringUtil;

class Dangdang
{

    const CATES_START_URL = 'http://category.dangdang.com/cp01.41.00.00.00.00.html';
    const BOOK_URL = 'http://product.dangdang.com/[BOOKID].html';
    const BOOK_URL_REGEX = 'http\:\/\/product\.dangdang\.com\/(\d+)\.html';

    public static $catesUrls = [];

    public static function getCatesUrls(&$page)
    {
        if (preg_match('/<\/div><span class="sp">(.+?)<\/div>/', $page, $m)) {
            if (preg_match_all('/<span><a href="(\/cp[0-9\.]+\.html)".+?>.+?<\/a><\/span>/', $m[1], $mm)) {
                for ($i = 0; $i < count($mm[0]); $i++) {
                    (self::$catesUrls)[] = 'http://category.dangdang.com' . $mm[1][$i];
                }
            }
        }
        if (empty(self::$catesUrls)) {
            return false;
        }

        self::$catesUrls[] = self::CATES_START_URL;
        return true;
    }

    public static function getList($url, &$page, &$books, $source)
    {
        if (preg_match_all('/<li ddt\-pit="\d+".+?>(.+?)<\/li>/', $page, $m)) {
            for ($i=0; $i<count($m[0]); $i++) {
                if (!preg_match('/当当自营/', $m[1][$i])) {
                    continue;
                }
                if (preg_match('/<li.+?ddt\-pit="\d+".+?>.+?<a.+?href="' . self::BOOK_URL_REGEX . '".+?>/', $m[1][$i], $mm)){
                    $bookPrice = new BookPrice();
                    $bookBasic = new BookBasic();

                    $bookPrice->source = $source;
                    $bookPrice->source_id = $mm[1];
                    $bookPrice->url = str_replace('[BOOKID]', $mm[1], self::BOOK_URL);

                    $book['book_price'] = $bookPrice;
                    $book['book_basic'] = $bookBasic;

                    $books[] = $book;
                }
            }
        }

        // 下一页
        if (preg_match('/<li class="next"><a href="(.+?)".*?>下一页<\/a><\/li>/', $page, $m)) {
            $nextUrl = StringUtil::createAbsoluteUrl($url, $m[1]);
            return $nextUrl;
        }
        return false;
    }

    public static function getBook(&$page, &$book, &$downloader)
    {
        if (!preg_match('/<span class="dang_red">/', $page)) {
            return false;
        }
        if (preg_match('/<img id="largePic".+?src="(.+?)".+?>/', $page, $m)) {
            $book['book_basic']->cover_url = trim($m[1]);
        }
        if (preg_match('/<h1.+?>(.+?)<\/h1>/', $page, $m)) {
            $book['book_basic']->name = trim($m[1]);
        }
        if (preg_match('/<span.+?id="author".+?>作者:(.+?)<\/span>/', $page, $m)) {
            $book['book_basic']->author = trim(preg_replace('/<.+?>/', '', $m[1]));
        }
        if (preg_match('/<span.+?>出版社:(.+?)<\/span>/', $page, $m)) {
            $book['book_basic']->publisher = trim(preg_replace('/<.+?>/', '', $m[1]));
        }
        if (preg_match('/<li>印刷时间：(\d+)年(\d+)月(\d+)日<\/li>/', $page, $m)) {
            $book['book_basic']->pubdate = $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/<li>国际标准书号ISBN：(\d+)<\/li>/', $page, $m)) {
            $book['book_basic']->isbn = trim($m[1]);
        }
        if (preg_match('/<label>所属分类：<\/label>(.*?)<\/ul>/', $page, $m)) {
            if (preg_match_all('/<a[^<>]+?>(.+?)<\/a>/', $m[1], $mm)) {
                $tagArr = [];
                for ($i=0; $i<count($mm[0]); $i++) {
                    $tmpArr = explode('/', $mm[1][$i]);
                    foreach ($tmpArr as $tag) {
                        if ($tag == '图书') {
                            continue;
                        }
                        $tagArr[$tag] = '';
                    }
                }
                $book['book_basic']->tags = empty($tagArr) ? '' : implode(' ', array_keys($tagArr));
            }
        }
        $prices = self::getBookPrice($page, $book['book_price']->source_id, $downloader);
        if (isset($prices['dprice'])) {
            $book['book_basic']->price = $prices['dprice'];
        }
        if (isset($prices['price'])) {
            $book['book_price']->price = $prices['price'];
        }
        return true;
    }

    public static function getBookPrice(&$page, $sourceBookId, &$downloader)
    {
        $prices = [];
        if (preg_match('/<p id="dd\-price">\s*<span class="yen">&yen;<\/span>([0-9\.]+)\s*<\/p>/', $page ,$m)) {
            $prices['price'] = intval($m[1] * 100);
        }
        if (preg_match('/<div class="price_m" id=\'original\-price\'>\s*<span class="yen">&yen;<\/span>([0-9\.]+)\s*<\/div>/', $page, $m)) {
            $prices['dprice'] = intval($m[1] * 100);
        }
        return $prices;
    }
}

