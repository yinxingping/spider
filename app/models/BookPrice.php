<?php

class BookPrice extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=10, nullable=false)
     */
    public $id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=10, nullable=false)
     */
    public $book_id;

    /**
     *
     * @var string
     * @Column(type="string", length=12, nullable=false)
     */
    public $source;

    /**
     *
     * @var string
     * @Column(type="string", length=32, nullable=false)
     */
    public $source_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=10, nullable=true)
     */
    public $price;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $url;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $created_at;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("spider");
        $this->setSource("book_price");
        $this->belongsTo('book_id', '\BookBasic', 'id', ['alias' => 'BookBasic']);

        $this->addBehavior(
            new \Phalcon\Mvc\Model\Behavior\Timestampable(
                [
                    'beforeCreate' => [
                        'field' => 'created_at',
                        'format'=> 'Y-m-d H:i:s',
                    ]
                ]
            )
        );

    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'book_price';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return BookPrice[]|BookPrice|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return BookPrice|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
