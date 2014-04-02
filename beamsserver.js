/*

Beams Server v0.1.0
Copyright (c) 2014, Peter McKay
Free to use under MIT license

*/




var express = require('express');

var app = express();

// The number of milliseconds in one day

var oneDay = 86400000;

// Use compress middleware to gzip content...

app.use(express.compress());

// Serve up content...

app.use(express.static(__dirname + '/html', { maxAge: oneDay }));

app.listen(process.env.PORT || 80);

console.log('Eureka! Server is running at port 80.');


