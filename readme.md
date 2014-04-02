#Beams Server (v0.1.0)

<<<<<<< HEAD
This is a simple Node.js/Express server module for publishing websites and applications built with the Beams development framework. 

Beams also includes a front-end module and an optional plugin for ad display. Along with the server, these modules are designed to constitute a full-stack development solution for media-rich projects like news sites, video-heavy web apps, blogs, and so on. 

All the Beams modules were created by me on behalf of Roscoe Labs. They're free to use under MIT license. 

For a fuller introduction to Beams, visit http://roscoelabs.com/products/beams or http://github.com/roscoelabs

Please note, we do consider this version of the server module to be an alpha release. We're eager to develop a community around the software to keep improving it from here. In that spirit, we welcome contributions of all kinds via GitHub, including forks, pull requests, bug reports, feature suggestions, and so on.

There's also a Beams Working Group email list that anyone can join at https://groups.google.com/forum/#!forum/beams-working-group

Now, on to some specifics about the server module. I'll admit, I'm more or less a Node.js newbie, so I'm going to cover a lot of basics here...


###Before deploying

There are a few dependencies you'll have to take care of before deploying Beams Server. The most important one is that you'll need to install Node.js and the npm package manager on your machine.

A few different methods to do this are documented in various places around on the Web. A very good one from the cloud provider Roscoe Labs itself uses, Digital Ocean, is at https://www.digitalocean.com/community/articles/how-to-install-express-a-node-js-framework-and-set-up-socket-io-on-a-vps

A few other pointers/caveats to keep in mind...

Current version of Node.js is 10.26 as of this writing. For reference purposes, you can always check what the latest release is at the Node homepage at http://nodejs.org.

If you want to check what version of node is actually loaded on your machine, run the following command:

`node -v`

In addition to the stock installation of Node.js, you'll also need the add-on module Express, which provides fuller support for Web applications. A copy of Express v3.5.1 is included in this repo, but I just point it out because you'll want to keep Express updated if you're maintaining a machine running Beams Server over time.

To check which version of Express you're running, use the following command:

`npm info express version`

Full documentation is available regarding Express at http://expressjs.com/

Finally, although it's not required, I'd recommend installing the Node.js module Forever on your machine. It will keep Beams Server running persistently, restart it automatically after any crashes, improve your uptime, etc.

To install Forever, run: 

`sudo npm install -g forever`

A good discussion of Forever, its features, and related commands is in the comments at http://stackoverflow.com/questions/12701259/how-to-make-a-node-js-application-run-permanently



###Deploying Beams Server

To start the server using the command line, go to the directory where you've stored the file beamsserver.js. Enter the command: 

`node beamsserver.js`

If you've installed the optional Forever module, you could also start by typing: 

`forever beamsserver.js`

After starting, beamsserver.js will serve up any web pages in its sibling /html folder. The server renders static web pages for now, though we may add more app-like features over time via Express.

Note that the /html folder has a subfolder of its own, labeled /blog. It's all static as well for now, with the only true blog-like feature being that the subfolder contains a boilerplate XML file to generate an RSS feed for whatever files you add to /blog. (Of course, you'll also have to add them manually as items in the xml outline for them to go out in the feed to subscribers.)

The /blog folder is one area in particular that should be ripe for rapid iteration and expanded functionality. Obviously, we need a login and content-management system to manipulate the other assets in the /blog folder. A fork of Ghost (https://github.com/tryghost/Ghost) or Fargo Publisher (https://github.com/scripting/fargoPublisher) seem like two possible solutions offhand, but I'm certainly open to other suggestions.


Peter McKay   
Co-Founder/Chief Product Officer   
Roscoe Labs   
April 2, 2014   
=======
Coming soon: A simple Node.js server to run Web applications made in the Beams framework.
>>>>>>> 809c1af2512b8527db32b37054ab646c7b4bcca4
