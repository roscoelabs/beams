#Beams v0.1.2 (Beta)

Beams is an open framework for building media websites, including news sites, blogs, video apps, et cetera. 

Roscoe Labs is the lead developer of Beams, which is free to use under MIT License. If you're new to using the framework, we encourage you to check out the comprehensive "Getting Started" guide at http://roscoelabs.com/beams/getting-started.html

Of course, we also encourage and welcome contributions and feedback from anyone interested in making Beams better. There are many ways for technical and non-technical contributors alike to participate, including our company GitHub page at https://github.com/roscoelabs and the Beams Working Group listserv at http://bit.ly/1rF4j0G

###Features

Beams v0.1.2 is a beta release with the following features:

- **Node.js/Express Web server.** A simple, fast server script to get your project up and running quickly. A basic website file structure is also included for serving static pages of content.

- **Ultraresponsive layout.** Layout is based on a 20-column, 4,000-pixel grid. This allows elegant resizing of elements on any size screen, from a large TV down to a smartphone. Video players are also responsive, including support for embedded players for YouTube, Vimeo, and self-hosted HTML5 video.

- **Default typefaces from Google Fonts.** We use Merriweather for headlines and Open Sans for pretty much everything else. (Of course, implementers should feel free to change these to something else if your project requires.)

- **jQuery 2.1.3.** The framework's `beams-client.js` file includes a full, minified copy of this popular JavaScript library so your project can quickly call functions from it.

###Usage

To implement Beams, you'll need a computer to use as a server that has the following software installed.

- **Node.js 0.10.26 or later.** Popular server-side JavaScript platform that uses Google's V8 engine.

- **npm 1.4.3 or later.** The node package manager. Helps add modules that enhance functionality of the stock version of Node.js. 

- **Express 4.10.4 or later.** Add-on Node.js module for building full-featured Web apps.

- **Forever 0.10.11 or later.** Add-on Node.js module that keeps the server running persistently.

Once all the requisite software is installed on your machine, navigate via the terminal to the directory where the file beamsserver.js is located and enter the following command:

`forever start beams-server.js --watch`

That's it. Server should now be running, and you should see a confirmation message in your command line that reads: "Eureka! Server is running at port 80." It should also be watching for file changes, to help on projects where site content is changing frequently.

After starting, beamsserver.js will serve up any web pages in its sibling folder labeled "site." 

If you ever need to stop the server, type:

`forever stop beams-server.js`

To restart: 

`forever restart beams-server.js --watch`
 
###Using the "Site" Folder

Once your Beams server is running, it will render the html files in the folder labeled "site" as Web pages, using the CSS, JavaScript and media files in the related subfolders. You can edit your site by adding or changing files in the "site" folder at any time.

To preserve the full functionality of Beams as you add pages, be sure to include everything in the `<head>` tag of the default "index.html" file on any new pages you create. This will have all the links you need to CSS files, JavaScript libraries, and so on.

###The Ultraresponsive Grid

In the folder site/css, you'll find the files for the Beams responsive layout. It's based on a 20-column, 4,000-pixel grid. 

The basic building block of pages is full-width containers with the class "beams" that act as rows to put several child elements in, like so...

	<div class="beam">
		<div class="tencolumn">
			<!-- Some stuff here-->
		</div>

		<div class="eightcolumn">
			<!-- Some stuff here-->
		</div>
	</div> <!-- End of beam -->

The "beam" class may be applied to any type of element. Likewise for any child containers, which can have classses of "onecolumn" to "twentycolumn" to specify how many columns each should take up. Total widths of the children should add up to no more than twenty, since that's how wide each beam is as the parent container.

One caveat: Elements with column-based classes ("onecolumn", "twocolumn," and so on) should only be direct children of an element with the "beams" class. Nesting them any deeper in the DOM may cause the design to break.

###Making Video Responsive

To make your YouTube, Vimeo, or self-hosted HTML5 videos resize responsively, just do the following:

- Make sure the head element of your page with videos contains links to the Beams files css/beams.css and js/beams-client.js. 

- If your video is an embed from YouTube or Vimeo, add the attribute `class="video"` to the iframe markup from the third-party provider. (You can skip this step if you're using self-hosted video.)
 
That's it. Your videos should now maintain a consistent rectangular 16:9 aspect ratio, like a widescreen television, even as you resize the browser viewport. 


<p align='center'>* * * * *</p>


Well, that's it for now. 

At Roscoe Labs, we have a mantra: "Open media. Open communities." We see Beams as a natural outgrowth of that idea, and we truly hope it helps gets your next project up and running soon, to make the Web just a little richer for everyone.

Good luck!

Peter McKay  
Co-Founder/Chief Product Officer  
Roscoe Labs  
Twitter: @peteramckay  

