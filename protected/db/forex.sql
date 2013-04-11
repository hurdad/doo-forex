delimiter $$

CREATE TABLE `agg_day` (
  `pair` varchar(7) NOT NULL,
  `ts` date NOT NULL,
  `bid_open` decimal(8,5) NOT NULL,
  `bid_high` decimal(8,5) NOT NULL,
  `bid_low` decimal(8,5) NOT NULL,
  `bid_close` decimal(8,5) NOT NULL,
  `offer_open` decimal(8,5) NOT NULL,
  `offer_high` decimal(8,5) NOT NULL,
  `offer_low` decimal(8,5) NOT NULL,
  `offer_close` decimal(8,5) NOT NULL,
  `vol` bigint(20) NOT NULL,
  PRIMARY KEY (`pair`,`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED
/*!50500 PARTITION BY LIST  COLUMNS(pair)
(PARTITION p01 VALUES IN ('AUD/USD') ENGINE = InnoDB,
 PARTITION p02 VALUES IN ('EUR/CHF') ENGINE = InnoDB,
 PARTITION p03 VALUES IN ('EUR/GBP') ENGINE = InnoDB,
 PARTITION p04 VALUES IN ('EUR/JPY') ENGINE = InnoDB,
 PARTITION p05 VALUES IN ('EUR/USD') ENGINE = InnoDB,
 PARTITION p06 VALUES IN ('GBP/JPY') ENGINE = InnoDB,
 PARTITION p07 VALUES IN ('GBP/USD') ENGINE = InnoDB,
 PARTITION p08 VALUES IN ('USD/CAD') ENGINE = InnoDB,
 PARTITION p09 VALUES IN ('USD/CHF') ENGINE = InnoDB,
 PARTITION p10 VALUES IN ('USD/JPY') ENGINE = InnoDB) */$$


delimiter $$

CREATE TABLE `agg_hour` (
  `pair` varchar(7) NOT NULL,
  `ts` datetime NOT NULL,
  `bid_open` decimal(8,5) NOT NULL,
  `bid_high` decimal(8,5) NOT NULL,
  `bid_low` decimal(8,5) NOT NULL,
  `bid_close` decimal(8,5) NOT NULL,
  `offer_open` decimal(8,5) NOT NULL,
  `offer_high` decimal(8,5) NOT NULL,
  `offer_low` decimal(8,5) NOT NULL,
  `offer_close` decimal(8,5) NOT NULL,
  `vol` bigint(20) NOT NULL,
  PRIMARY KEY (`pair`,`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED
/*!50500 PARTITION BY LIST  COLUMNS(pair)
(PARTITION p01 VALUES IN ('AUD/USD') ENGINE = InnoDB,
 PARTITION p02 VALUES IN ('EUR/CHF') ENGINE = InnoDB,
 PARTITION p03 VALUES IN ('EUR/GBP') ENGINE = InnoDB,
 PARTITION p04 VALUES IN ('EUR/JPY') ENGINE = InnoDB,
 PARTITION p05 VALUES IN ('EUR/USD') ENGINE = InnoDB,
 PARTITION p06 VALUES IN ('GBP/JPY') ENGINE = InnoDB,
 PARTITION p07 VALUES IN ('GBP/USD') ENGINE = InnoDB,
 PARTITION p08 VALUES IN ('USD/CAD') ENGINE = InnoDB,
 PARTITION p09 VALUES IN ('USD/CHF') ENGINE = InnoDB,
 PARTITION p10 VALUES IN ('USD/JPY') ENGINE = InnoDB) */$$


delimiter $$

CREATE TABLE `quotes` (
  `pair` varchar(7) NOT NULL,
  `ts` datetime(3) NOT NULL,
  `bid` decimal(8,5) NOT NULL,
  `offer` decimal(8,5) NOT NULL,
  PRIMARY KEY (`pair`,`ts`,`bid`,`offer`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED
/*!50500 PARTITION BY LIST  COLUMNS(pair)
(PARTITION p01 VALUES IN ('AUD/USD') ENGINE = InnoDB,
 PARTITION p02 VALUES IN ('EUR/CHF') ENGINE = InnoDB,
 PARTITION p03 VALUES IN ('EUR/GBP') ENGINE = InnoDB,
 PARTITION p04 VALUES IN ('EUR/JPY') ENGINE = InnoDB,
 PARTITION p05 VALUES IN ('EUR/USD') ENGINE = InnoDB,
 PARTITION p06 VALUES IN ('GBP/JPY') ENGINE = InnoDB,
 PARTITION p07 VALUES IN ('GBP/USD') ENGINE = InnoDB,
 PARTITION p08 VALUES IN ('USD/CAD') ENGINE = InnoDB,
 PARTITION p09 VALUES IN ('USD/CHF') ENGINE = InnoDB,
 PARTITION p10 VALUES IN ('USD/JPY') ENGINE = InnoDB) */$$


delimiter $$

CREATE TABLE `quotes_live` (
  `pair` varchar(7) NOT NULL,
  `ts` datetime(3) NOT NULL,
  `bid` decimal(8,5) NOT NULL,
  `offer` decimal(8,5) NOT NULL,
  PRIMARY KEY (`pair`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1$$



