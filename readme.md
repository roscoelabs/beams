#Beams v0.1.1 (Beta)

Beams is an open, full-stack framework for building media websites, including news sites, blogs, video apps, and so on. 

This version consolidates what had previously been three separate code repos -- a server module, an ultraresponsive front-end grid, and a plugin for ad display and metrics. The idea is that by putting it all together, in one place, developers will find it much easier to get started using Beams in their projects.

Roscoe Labs is the lead developer of Beams, which is free to use under MIT License. We encourage and welcome anyone interested in making the software better to participate in the Beams Working Group at http://bit.ly/1rF4j0G

###Features

Beams v0.1.1 is a beta release with the following features:

-**Node.js/Express Web server.** A simple server script to get your project up and running fast. A basic file structure is included for serving static pages of content, while the inclusion of the Node.js Express module also allows for buildout of more full-featured Web apps if need be.

-**Ultraresponsive front end.** Layout is based on a 20-column, 4,000-pixel grid. This allows elements to resize elegantly for any screen from a large-screen TV to a smartphone. Video players are also responsive, thanks to the Fitvids.js plugin. Players for embedded YouTube videos, Vimeo, and self-hosted HTML5 video are supported.

-**Minified jQuery v2.1.1 library.** A locally hosted copy so your project can quickly call functions from the popular JavaScript tool.

-**Interstitial ad display and ad tracking.**  Display a popup ad on any page, set cookies, and track views using Google Analytics.

###Usage

To implement Beams, you'll need a computer to use as a server that has the following software installed.

-**Node.js v0.10.26 or later.** Popular server-side JavaScript platform that uses Google's V8 engine. Current version is 0.10.33 as of this writing.

- **npm v1.4.3 or later.** The node package manager. Helps add modules that enhance functionality of the stock version of Node.js. *((Does this really require a separate install? Check offline.))*

-**Express v4.10.4 or later.** Add-on Node.js module for building full-featured Web apps.

-**Forever v0.10.11 or later.** Add-on Node.js module that keeps the server running persistently.

We'll walk through step-by-step installation of these required Node tools in subsequent sections of this document.

Optionally, we recommend that you install the following software on your machine as well, though it's not requried to get up and running:

-**Ubuntu v12.04 LTS or later.** This is the operating system we've tested Beams on, and we run it on our production server for roscoelabs.com as well. For full info on Ubuntu, visit http://www.ubuntu.com 

-**Nginx.** A reverse-proxy tool. Useful if you want to integrate Beams with a CMS like WordPress or Drupal running on a separate server. For full info on Nginx, visit http://www.ubuntu.com

-**MongoDB.** Open tool for building and managing databases. For full info, see http://www.mongodb.org/

###Server Setup

Of course, the first step is to install Node.js on your machine. There are a number of installation methods documented around the Web, but we'll walk through a few good ones here briefly. 

On our Ubuntu test servers for v0.1.1, we installed the current versions of both Node and npm in one bundle using the following commands. (The second line references a server maintained by the enterprise software company Nodesource.)

`sudo apt-get curl`

`curl -sL https://deb.nodesource.com/setup | sudo bash -`

`sudo apt-get install nodejs`

An alternative, even easier command-line method that works follows below. This one involves installing Node.js and npm separately:

`sudo apt-get install node`

`sudo apt-get install npm`

Only downside of the latter method is you don't get the very latest versions installed, but they will run just fine. As of this writing, this method gets you node v0.10.26 versus the current v0.10.33.

If you're running a different OS, or if you prefer to use a desktop download over command line, the current version of Node bundled with npm is available for download via the Node project homepage at http://nodejs.org/download/  Page includes install files for OS X, Windows, and Linux.





Following method installs Node.js and npm, though not the latest versions:














There are also a few different methods to install from the command line documented on various sites around on the Web. 

http://bit.ly/1vlVJDL



###The Ultraresponsive Grid

###Responsive Video

###Ad Display & Metrics
