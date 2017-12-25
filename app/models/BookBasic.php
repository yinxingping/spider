<?php

class BookBasic extends \Phalcon\Mvc\Model
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
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $name;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    public $author;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    public $publisher;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $pubdate;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    public $tags;

    /**
     *
     * @var string
     * @Column(type="string", length=13, nullable=true)
     */
    public $isbn;

    /**
     *
     * @var integer
     * @Column(type="integer", length=10, nullable=true)
     */
    public $price;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $cover;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $cover_url;

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
        $this->setSource("book_basic");
        $this->hasMany('id', 'BookPrice', 'book_id', ['alias' => 'BookPrice']);

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
        return 'book_basic';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return BookBasic[]|BookBasic|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return BookBasic|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
