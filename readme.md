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

-**Node.js.** Popular server-side JavaScript platform that uses Google's V8 engine.

-**Express.** Add-on Node.js module for building full-featured Web apps.

-**Forever.** Add-on Node.js module that keeps the server running persistently.

We'll walk through step-by-step installation of these required Node tools in subsequent sections of this document.

Optionally, we recommend that you also install the following software on your machine, though it's not needed to get up and running initially:

-**Ubuntu v12.04 or v14.04.** These are the operating systems on the computers we've tested and used Beams on so far at Roscoe Labs. The software should work well on other Linux distros, Windows, or OS X as well, however. For full info on Ubuntu, visit http://www.ubuntu.com

-**Nginx.** A reverse-proxy tool. Useful if you want to integrate Beams with a CMS like WordPress or Drupal running on a separate server. For full info on Nginx, visit http://www.ubuntu.com

-**MongoDB.** Open tool for building and managing databases. For full info, see http://www.mongodb.org/

###Server Setup

###The Ultraresponsive Grid

###Responsive Video

###Ad Display & Metrics
