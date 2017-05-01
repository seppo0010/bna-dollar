CREATE DATABASE bna_test;
USE bna_test;

CREATE TABLE currency (
    date DATE NOT NULL PRIMARY KEY,
    dollar_buy DECIMAL(10, 2) NULL,
    dollar_sell DECIMAL(10, 2) NULL,
    euro_buy DECIMAL(10, 2) NULL,
    euro_sell DECIMAL(10, 2) NULL
)
