Beams Server
==========================

This is a simple Node.js server to run Web applications made in the Beams framework. 

A very important prerequisite: Prior to running the code in the main file `beamsserver.js`, Node.js and the npm package manager must be installed on your machine. A few different methods to do this are documented in various places around on the Web. After some trial and error, I ultimately decided to install from a repo maintained by developer Chris Lea, as described at http://stackoverflow.com/questions/16302436/install-nodejs-on-ubuntu-12-10

	sudo apt-get install python-software-properties python g++ make
	sudo add-apt-repository ppa:chris-lea/node.js
	sudo apt-get update
	sudo apt-get install nodejs

Chief advantage of this method, as of my own installation as part of an earlier project in late December 2013, is that it added the most updated version of Node.js (v0.10.24 at the time) to my machine running Ubuntu 12.04. By comparison, the simpler method described at nodejs.org using the command `sudo apt-get install nodejs`, installed an older version of Node.js (v0.6). That older version was incompatible with certain node.js add-on modules, including the Express web application framework (http://expressjs.com/).

Install Express module using this command:

`npm install express`

For maintenance, use `sudo apt-get update` to get updates of node.js. Us `node -v` if you ever need to check which version you're running.

Once up-to-date versions of node.js, npm, and and any necessary extra modules like Express are installed on your machine, run the following command within the beamsserver repo to start the server...

`node beamsserver.js`

Beams Ads is free to use under MIT license. This is a v0.1 alpha version of the software, which we plan to imrpove over time. Pull requests and feedback are also welcome, of course.

Peter McKay
Co-Founder/Chief Product Officer
Roscoe Labs
