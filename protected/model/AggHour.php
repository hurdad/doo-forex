<?php
Doo::loadCore('db/DooModel');

class AggHour extends DooModel{

    /**
     * @var varchar Max length is 7.
     */
    public $pair;

    /**
     * @var datetime
     */
    public $ts;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $bid_open;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $bid_high;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $bid_low;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $bid_close;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $offer_open;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $offer_high;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $offer_low;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $offer_close;

    /**
     * @var bigint Max length is 20.
     */
    public $vol;

    public $_table = 'agg_hour';
    public $_primarykey = 'ts';
    public $_fields = array('pair','ts','bid_open','bid_high','bid_low','bid_close','offer_open','offer_high','offer_low','offer_close','vol');

    public function getVRules() {
        return array(
                'pair' => array(
                        array( 'maxlength', 7 ),
                        array( 'notnull' ),
                ),

                'ts' => array(
                        array( 'datetime' ),
                        array( 'notnull' ),
                ),

                'bid_open' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'bid_high' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'bid_low' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'bid_close' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'offer_open' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'offer_high' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'offer_low' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'offer_close' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'vol' => array(
                        array( 'integer' ),
                        array( 'maxlength', 20 ),
                        array( 'notnull' ),
                )
            );
    }

}