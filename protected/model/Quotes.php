<?php
Doo::loadCore('db/DooModel');

class Quotes extends DooModel{

    /**
     * @var varchar Max length is 7.
     */
    public $pair;

    /**
     * @var datetime Max length is 3.
     */
    public $ts;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $bid;

    /**
     * @var decimal Max length is 8. ,5).
     */
    public $offer;

    public $_table = 'quotes';
    public $_primarykey = 'offer';
    public $_fields = array('pair','ts','bid','offer');

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

                'bid' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                ),

                'offer' => array(
                        array( 'float' ),
                        array( 'notnull' ),
                )
            );
    }

}