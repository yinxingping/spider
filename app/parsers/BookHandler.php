<?php

namespace Spider\Parser;

use OSS\Core\OssException;
use Spider\Library\StringUtil;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;

/**
 * 负责图书详情下载、解析、验证、保存等工作
 */
class BookHandler implements InjectionAwareInterface
{

    protected $di;

    public function setDI(DiInterface $di)
    {
        $this->di = $di;
    }

    public function getDI()
    {
        return $this->di;
    }

    /**
     * 生成图书类别数组
     */
    public function parseCatesUrls(&$parser)
    {
        $page = $this->di->getDownloader()->getPage($parser::CATES_START_URL, 'CATES');
        if (!$page) {
            return false;
        }
        if ($parser::getCatesUrls($page)) {
            return true;
        }

        return false;
    }

    /**
     * 解析列表，并返回下一页地址
     */
    public function parseList($url, $parser, $source)
    {
        $page = $this->di->getDownloader()->getPage($url, 'LIST');
        if (!$page) {
            return false;
        }

        $books = [];
        $nextUrl = $parser::getList($url, $page, $books, $source);
        foreach ($books as $book) {
            $this->parseBook($parser, $book);
        }

        return $nextUrl;
    }

    /**
     * 根据url解析图书
     */
    public function parseUrl(&$params)
    {
        $book['book_basic'] = new \BookBasic();
        $book['book_price'] = new \BookPrice();

        $book['book_price']->url = $params['url'];
        $book['book_price']->source = $params['source'];
        $book['book_price']->source_id = $params['source_id'];

        return $this->parseBook($params['parser'], $book);
    }

    /**
     * 更新价格
     */
    public function parsePrices(&$bookPrice)
    {
        $downloader = $this->di->getDownloader();

        $params = $this->getParams($bookPrice->url);
        if ($params['source'] != 'jd') {
            $page = $downloader->getPage($params['url'], 'PRICE');
            if (!$page) {
                return false;
            }
        }

        $prices = $params['parser']::getBookPrice($page, $params['source_id'], $downloader);
        if (empty($prices)) {
            return false;
        }
        $this->updateBookPrice($bookPrice, $prices);

        return true;
    }

    /**
     * 具体解析并保存／更新图书
     */
    private function parseBook($parser, $book)
    {
        //已经入库的图书不再重复抓取
        $bookPrice = \BookPrice::findFirst([
            'source = :source: AND source_id = :source_id:',
            'bind' => [
                'source' => $book['book_price']->source,
                'source_id' => $book['book_price']->source_id,
            ],
        ]);
        if ($bookPrice) {
            return $bookPrice->book_id;
        }

        $downloader = $this->di->getDownloader();
        $page = $downloader->getPage($book['book_price']->url, 'BOOK');
        if (!$page) {
            return false;
        }
        if (!$parser::getBook($page, $book, $downloader)) {
            $this->di->getLogger()->info('非自营图书不解析');
            return false;
        }

        $bookId = 0;
        if (self::checkOk($book)) {
            if ($book['book_basic']->isbn) {
                //新来源且存在合法的isbn
                $bookBasic = \BookBasic::findFirst([
                    'isbn = :isbn:',
                    'bind' => [
                        'isbn' => $book['book_basic']->isbn,
                    ],
                ]);
                //基本信息已存在仅需增加新的来源
                if ($bookBasic) {
                    $bookId = $bookBasic->id;
                    $this->saveBookPrice($bookBasic->id, $book['book_price']);
                } else {
                    $bookId = $this->saveBook($book);
                }
            } else {
                //新来源且isbn为空
                $bookId = $this->saveBook($book);
            }
        }

        return $bookId;
    }

    /**
     * 判断指定url属于哪个图书解析器负责,并返回必要信息
     */
    public function getParams($url)
    {
        $params = [];
        foreach ($this->di->getConfig()->books as $source => $parser) {
            if (preg_match('/' . $parser::BOOK_URL_REGEX . '/', $url, $m)) {
                $params['url'] = ($source == 'jd' ? 'https://' : '') . trim($m[0]);
                $params['source'] = $source;
                $params['source_id'] = $m[1];
                $params['parser'] = $parser;
                break;
            }
        }

        return $params;
    }

    /**
     * 检查图书信息是否完善并进行转换和清理
     */
    private function checkOk(&$book)
    {
        $bookFieldMust = [
            'book_basic' => ['name', 'cover_url'],
            'book_price' => ['source', 'source_id', 'url'],
        ];

        //过滤掉文字中混杂的其他内容
        $name = $book['book_basic']->name;
        StringUtil::trimTag($name);
        $name = mb_substr($name, 0, 255, 'UTF-8');
        $book['book_basic']->name = $name;

        $author = $book['book_basic']->author;
        StringUtil::trimTag($author);
        $author = mb_substr($author, 0, 255, 'UTF-8');
        $book['book_basic']->author = $author;

        $publisher = $book['book_basic']->publisher;
        StringUtil::trimTag($publisher);
        $publisher = mb_substr($publisher, 0, 255, 'UTF-8');
        $book['book_basic']->publisher = $publisher;

        if (
            isset($book['book_basic']->pubdate) &&
            !preg_match('/^\d{4}\-\d{1,2}\-\d{1,2}/', $book['book_basic']->pubdate)
            ) {
            unset($book['book_basic']->pubdate);
        }

        foreach ($bookFieldMust as $key => $fields) {
            foreach ($fields as $field) {
                if (empty($book[$key]->$field)) {
                    $this->di->getLogger()->info($book['book_price']->url . "\t" . $field . " must have value.");
                    return false;
                }
            }
        }
        $book['book_basic']->isbn = strlen($book['book_basic']->isbn) != 13 ? '' : $book['book_basic']->isbn;

        return true;
    }

    private function saveBook(&$book)
    {
        $img = null;
        if ($this->downloadCover($book['book_basic']->cover_url, $img)) {
            $book['book_basic']->cover = 1;
        }

        $db = $this->di->getDb();
        $db->begin();
        if ($book['book_basic']->save() === false) {
            $db->rollback();
            return false;
        }
        $book['book_price']->book_id = $book['book_basic']->id;
        if ($book['book_price']->save() === false) {
            $db->rollback();
            return false;
        }
        $db->commit();

        $this->saveCover($book['book_basic']->id, $img);

        return $book['book_basic']->id;
    }

    private function saveBookPrice($bookId, &$bookPrice)
    {
        $bookPrice->book_id = $bookId;

        return $bookPrice->save();
    }

    /**
     * 更新图书价格
     */
    private function updateBookPrice(&$bookPrice, &$prices)
    {
        if (empty($prices['price']) || $bookPrice->price == $prices['price']) {
            return false;
        }

        if (!empty($prices['dprice']) && empty($bookPrice->price)) {
            $bookBasic = \BookBasic::findFirst($bookPrice->book_id);
            $bookBasic->price = $prices['dprice'];
            $bookBasic->save();
        }

        $bookPrice->price = $prices['price'];
        $bookPrice->save();

        return true;
    }

    private function downloadCover($url, &$img)
    {
        $img = $this->di->getDownloader()->getImg($url);
        if (!$img) {
            return false;
        }

        return true;
    }

    private function saveCover($bookId, &$img)
    {
        $object = intval($bookId/100) . '/' . $bookId;
        try {
            $this->di->getOss()->putObject($this->di->getConfig()->oss->bucket, $object, $img);
        } catch (OssException $e) {
            $this->di->getLogger()->error($e->getMessage());
            return false;
        }

        return true;
    }

}

