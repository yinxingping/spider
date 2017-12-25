<?php

use Phalcon\Cli\Task;

class BookTask extends Task
{
    public function mainAction()
    {
echo <<<END
这是一个图书爬虫,具体用法为：
1. 抓取指定来源的图书信息,如：./spider book list jd
2. 更新书库中所有图书的卖价，如：./spider book price 'price=0'
END;
    }

    /**
     * 处理指定网站和条件的图书
     */
    public function listAction(Array $params)
    {
        $source = $params[0];
        $parser = $this->config['books'][$source];

        if (empty($parser::$catesUrls)) {
            if ($this->handler->parseCatesUrls($parser) === false) {
                $this->logger->error("获取图书类别列表错误");
                exit();
            }
        }

        $start = isset($params[1]) ? intval(trim($params[1])) : 0;
        for ($i=$start; $i<count($parser::$catesUrls); $i++) {
            $thisurl = $parser::$catesUrls[$i];
            while (true) {
                if ($thisurl = $this->handler->parseList($thisurl, $parser, $source)) {
                    continue;
                }
                break;
            }
        }
    }

    /**
     * 更新书库中图书价格
     */
    public function priceAction(Array $conditions)
    {
        $offset = 0;
        $step = 10000;
        //取价格连续失败次数
        $failedTimes = 0;
        //有5次连续失败则退出
        $failedTotalTimes = 5;
        while ($bookPrices = BookPrice::find([
            'conditions' => $conditions[0],
            'order' => 'id desc',
            'offset' => $offset,
            'limit' => $step
        ])) {
            if (count($bookPrices) == 0) {
                break;
            }
            foreach ($bookPrices as $bookPrice) {
                if ($this->handler->parsePrices($bookPrice) === false) {
                    $failedTimes ++;
                } else {
                    $failedTimes = 0;
                }
                if ($failedTimes >= $failedTotalTimes) {
                    break;
                }
            }
            if ($failedTimes >= $failedTotalTimes) {
                $this->logger->error('连续' . $failedTotalTimes . '次取不到价格，需要重启路由器以切换新IP');
                break;
            }
            $offset += $step;
        }
    }

}

