# Fetches dollar and euro prices from BNA

## Requirements

* php4+
* internet connection
* optionally, mysql

## Test

```
$ php cotizacion.php 2017-04-12 2017-04-16 1
{"Dolar U.S.A":{"11\/4\/2017":{"Compra":15.05,"Venta":15.45},"12\/4\/2017":{"Compra":15,"Venta":15.4},"17\/4\/2017":{"Compra":15,"Venta":15.4}},"Euro":{"11\/4\/2017":{"Compra":16.4,"Venta":17.4},"12\/4\/2017":{"Compra":16.4,"Venta":17.4},"17\/4\/2017":{"Compra":16.4,"Venta":17.4}}}
```
