/*

Beams v0.1.3 (Beta)
Copyright (c) 2014-2015 Peter McKay
Free to use under the MIT license.

*/

var express = require('express');
var app = express();
var mongo = require('mongoskin');
var Db = mongo.Db;
var oneDay = 86400000; 

app.use(express.static(__dirname + '/site', { maxAge: oneDay })); // Serve up site content...

app.listen(process.env.PORT || 1337);

console.log('Eureka! Server is running at port 1337.');
