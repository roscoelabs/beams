/*

Beams v0.1.2 (Beta)
Copyright (c) 2014-2015 Peter McKay
Free to use under the MIT license.

*/

var express = require('express');
var app = express();
var oneDay = 86400000; // The number of milliseconds in one day...

app.route('/site/js').all(function(req, res, next) {
});

app.route('/site/css').all(function(req, res, next) {
});

app.route('/site/img').all(function(req, res, next) {
});

app.route('/site/video').all(function(req, res, next) {
});

app.use(express.static(__dirname + '/site', { maxAge: oneDay })); // Serve up site content...

app.listen(process.env.PORT || 80);

console.log('Eureka! Server is running at port 80.');
