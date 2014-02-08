Beams Server
==========================

In this project, I'm making a basic Node.js server. Software is open source under MIT license.

Very important: Prior to running the code in the main file `hello_node.js`, Node.js and the npm package manager must be installed on your machine. A few different methods to do this are documented in various places around on the Web. After some trial and error, I ultimately decided to install from a repo maintained by developer Chris Lea, as described at http://stackoverflow.com/questions/16302436/install-nodejs-on-ubuntu-12-10

	sudo apt-get install python-software-properties python g++ make
	sudo add-apt-repository ppa:chris-lea/node.js
	sudo apt-get update
	sudo apt-get install nodejs

Chief advantage of this method, as of this writing on 12/28/13, is that it added the most updated version of Node.js (v0.10.24) to my machine running Ubuntu 12.04. By comparison, the simpler method described at nodejs.org using the command `sudo apt-get install nodejs`, installed an older version of Node.js (v0.6). That older version was incompatible with certain node.js add-on modules, including the Express web application framework (http://expressjs.com/).

(Note: A full copy of node.js v10.24 is included in this repo as well, but that's really just for the sake of study, in case I want to look through the source code for learning purposes.)

Installed Express module using this command:

`npm install express`

Once node.js, npm, and Express were installed on my machine, I ran the following command within the 'hello_node' repo to start my node.js server...

`node hello_node.js`

