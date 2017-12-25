<?php

namespace Spider\Parser\Book;

use Spider\Library\StringUtil;

class Jd
{
    const CATES_START_URL = 'https://book.jd.com/booksort.html';
    const BOOK_URL = 'https://item.jd.com/[BOOKID].html';
    const BOOK_URL_REGEX = '\/\/item\.jd\.com\/(\d+)\.html';

    public static $catesUrls = [];

    public static function getCatesUrls(&$page)
    {
        if (preg_match_all('/<em><a href="\/\/list\.jd\.com\/([0-9\-]+)\.html">.+?<\/a><\/em>/', $page, $m)) {
            for ($i = 0; $i < count($m[0]); $i++) {
                (self::$catesUrls)[] =
                    'https://list.jd.com/list.htm?cat='
                    . str_replace('-', ',', $m[1][$i])
                    . '&delivery=1&sort=sort_rank_asc&trans=1&JL=4_10_0#J_main';
            }
        }
        if (empty(self::$catesUrls)) {
            return false;
        }

        return true;
    }

    public static function getList($url, &$page, &$books, $source)
    {
        if (preg_match_all('/<li class="gl\-item".+?>(.+?)<\/li>/', $page, $m)) {
            for ($i=0; $i<count($m[0]); $i++) {
                if (!preg_match('/京东自营/', $m[1][$i])) {
                    continue;
                }
                if (preg_match('/<div class="p\-name">\s*<a[^<>]+?href="' . self::BOOK_URL_REGEX . '.*?>/', $m[1][$i], $mm)) {
                    $bookPrice = new \BookPrice();
                    $bookBasic = new \BookBasic();

                    $bookPrice->source = $source;
                    $bookPrice->source_id = $mm[1];
                    $bookPrice->url = str_replace('[BOOKID]', $mm[1], self::BOOK_URL);

                    $book['book_price'] = $bookPrice;
                    $book['book_basic'] = $bookBasic;

                    $books[] = $book;
                }
            }
        }

        if (preg_match('/<a\s+class=\'fp-next\'\s+href="(.+?)">\s*>\s*<\/a>/', $page, $m)) {
            $nextUrl = StringUtil::createAbsoluteUrl($url, $m[1]);
            return $nextUrl;
        }
        return false;
    }

    public static function getBook(&$page, &$book, &$downloader)
    {
        if (preg_match('/<div class="seller\-infor">(.+?)<\/div>/', $page, $m)) {
            if (!preg_match('/京东自营/', $m[1])) {
                return false;
            }
        }
        if (preg_match('/<div id="spec\-n1" class="jqzoom".+?>\s*<img data\-img="1" width="350" height="350" src="(.+?)".+?\/>/', $page, $m)) {
            $book['book_basic']->cover_url = 'http:' . trim($m[1]);
        }
        if (preg_match('/<h1>(.+?)<\/h1>/', $page, $m)) {
           $book['book_basic']->name = trim($m[1]);
        }
        if (preg_match('/<div class="p-author" id="p-author".+?>(.+?)<\/div>/', $page, $m)) {
            $book['book_basic']->author = trim(preg_replace('/<.+?>/', '', $m[1]));
        }
        if (preg_match('/<li.+?>出版社：(.+?)<\/li>/', $page, $m)) {
            $book['book_basic']->publisher = trim(preg_replace('/<.+?>/', '', $m[1]));
        }
        if (preg_match('/<li.+?>ISBN：\s*(\d+)\s*<\/li>/', $page, $m)) {
            $book['book_basic']->isbn = $m[1];
        }
        if (preg_match('/<li.+?>出版时间：(.+?)<\/li>/', $page, $m)) {
            $book['book_basic']->pubdate = trim($m[1]);
        }
        if (StringUtil::parse_substr($page, $substr, '<div class="breadcrumb">', '</div>')) {
            if (StringUtil::parse_substr($substr, $cateStr, '<span>', '</span>')) {
                if (preg_match_all('/<a.+?>(.+?)<\/a>/', $cateStr, $m)) {
                    $tagArr = [];
                    for ($i=0; $i<count($m[0]); $i++) {
                        $tmpArr = explode('/', $m[1][$i]);
                        foreach ($tmpArr as $tag) {
                            $tagArr[$tag] = '';
                        }
                    }
                    $book['book_basic']->tags = empty($tagArr) ? '' : implode(' ', array_keys($tagArr));
                }
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
        $priceUrl = 'https://p.3.cn/prices/get?skuid=J_' . $sourceBookId . '&type=1&callback=changeImgPrice2Num';

        $page = $downloader->getPage($priceUrl, 'PRICE');
        if (!$page) {
            return false;
        }
        //changeImgPrice2Num([{"id":"J_11461683","p":"53.00","m":"79.00","op":"53.00"}]);
        $prices = [];
        if (preg_match('/"m":"([0-9\.]+)"/', $page, $m)) {
            $prices['dprice'] = intval($m[1] * 100);
        }
        if (preg_match('/"op":"([0-9\.]+)"/', $page, $m)) {
            $prices['price'] = intval($m[1] * 100);
        }

        return $prices;
    }
}

