#Beams v0.1.2 (Beta)

Beams is an open, full-stack framework for building media websites, including news sites, blogs, video apps, et cetera. 

This version consolidates what had previously been three separate code repositories -- a server module, an ultraresponsive CSS3 layout, and a plugin for ad display and metrics. The idea is that by putting it all together, in one place, developers should find it much easier to get started using Beams in their projects.

Roscoe Labs is the lead developer of Beams, which is free to use under MIT License. We encourage and welcome anyone interested in making the software better to participate in the Beams Working Group at http://bit.ly/1rF4j0G

###Features

Beams v0.1.2 is a beta release with the following features:

- **Node.js/Express Web server.** A simple server script to get your project up and running quickly. A basic website file structure is included for serving static pages of content, while the inclusion of the Node.js Express module also allows for buildout of more full-featured Web apps if need be.

- **Ultraresponsive layout.** Layout is based on a 20-column, 4,000-pixel grid with 16-pixel gutters. This allows elements to resize elegantly using CSS3 media queries for any screen from a large-screen TV to a smartphone. Video players are also responsive, thanks to the Fitvids.js plugin. Players for embedded YouTube videos, Vimeo, and self-hosted HTML5 video are supported.

- **Default typefaces from Google Fonts.** We use Merriweather for headlines and Open Sans for pretty much everything else. Of course, implementers should feel free to change these to whatever else you want if necessary, use locally hosted fonts on your own server, et cetera.

- **jQuery 2.1.1.** Beams includes a locally hosted copy so your project can quickly call functions from the popular JavaScript library.

- **Interstitial ad display and ad tracking.**  Beams allows you to display a popup ad on any page, set cookies, and track views using Google Analytics. You can also turn ad display off if you don't want to interfere with users' browsing.

###Usage

To implement Beams, you'll need a computer to use as a server that has the following software installed.

- **Node.js 0.10.26 or later.** Popular server-side JavaScript platform that uses Google's V8 engine. Current version is 0.10.33 as of this writing.

- **npm 1.4.3 or later.** The node package manager. Helps add modules that enhance functionality of the stock version of Node.js. 

- **Express 4.10.4 or later.** Add-on Node.js module for building full-featured Web apps.

- **Forever 0.10.11 or later.** Add-on Node.js module that keeps the server running persistently.

We'll walk through step-by-step installation of these required Node tools in subsequent sections of this document.

Optionally, installing the following software on your machine may provide additional functionality to your Beams site, though it's not required to get up and running:

- **Ubuntu 12.10 or later.** Ubuntu is the operating system we've tested Beams on, and we run it on our production server for roscoelabs.com as well. For full info on Ubuntu, visit http://www.ubuntu.com (If Ubuntu isn't your cup of tea, no worries. Beams should work fine on other Linux distros, OS X, or Windows as well.)

- **MongoDB.** Open tool for building and managing databases. For full info, see http://www.mongodb.org/

- **Nginx.** A reverse-proxy tool. Useful if you want to integrate Beams with a CMS like WordPress or Drupal running on a separate server. Please note, however, this is a fairly advanced option. For full info on Nginx, visit http://www.ubuntu.com

###Server Setup

Of course, the first step is to install Node.js and npm on your machine. There are a number of installation methods documented around the Web, but we'll walk through a couple good ones here briefly. 

On our Ubuntu test servers for Beams 0.1.1, we installed the current versions of both Node and npm in one bundle using the following command. (This command should also work for Unix-based systems like OS X):

`sudo apt-get install nodejs`

If you're running a different OS, or if you prefer to use a desktop download over command line, the current version of Node bundled with npm is available for download via the Node project homepage at http://nodejs.org/download/  Page includes install files for Linux, OS X, and Windows.

Once you've installed Node.js and npm, you'll want to install Express. Command to get the latest version is:

`sudo npm install express`

Now install Forever:

`sudo npm install forever`

Now we're ready to start the Beams server! Navigate via the terminal to the directory where the file beamsserver.js is located and enter the following command:

`forever start beamsserver.js --watch`

That's it. Server should now be running, and you should see a confirmation message in your command line that reads: "Eureka! Server is running at port 80." It should also be watching for file changes, to help on projects where site content is changing frequently.

After starting, beamsserver.js will serve up any web pages in its sibling folder labeled "site." To speed up page loading, the server uses Express to gzip content and Node's built-in fs module to read photo and video files server-side prior to receiving client requests.

If you ever need to stop the server, type:

`forever stop beamsserver.js`

To restart: 

`forever restart beamsserver.js --watch`

For a fuller list of forever commands, including options to manage logs and so on, type: 

`forever --help`
 
###Using the "Site" Folder

Once your Beams server is running, it will render the html files in the folder labeled "site" as Web pages, using the CSS, JavaScript and media files in the related subfolders. You can edit your site by adding or changing files in the "site" folder at any time.

How you access and edit the site folder is outside the scope of this document. At Roscoe Labs, since we're using a cloud-based server on Digital Ocean, we use Secure Transfer Protocol to edit our company site. But you could edit the files locally, if you're using a physical server that you can sit down with, or you could use a third-party CMS like WordPress, Drupal, et cetera. 

In the latter scenario, we'd recommend running your CMS on a separate admin server and using Nginx to output your site's pages on your Beams server. 

A few other additional notes bout the "site" folder:

- It includes a subfolder labeled "rss" to hold RSS feeds for the various parts of your site. For now, these feeds have to be manually maintained, but we plan to automate the updates in future versions of Beams.

- It includes a robots.txt file, in case you want to hid certain files or directories on your site from crawling by search engines. By default, it's blank, but you can manually add filenames or directories as needed.

- To preserve the full functionality of Beams as you add pages, be sure to include everything in the `<head>` tag of the default "index.html" file on any new pages you create. This will have all the links you need to CSS files, JavaScript libraries, and so on.

###The Ultraresponsive Grid

In the folder site/css, you'll find the files for Beams 0.1.1's responsive layout. It's based on a 20-column, 4,000-pixel grid, with 16-pixel gutters. Total width of the grid is approximately the physical screen size of a 48-inch TV, yet pages will also scale down smoothly using media queries to fit any other size screen right down to smart phone as well.

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

One caveat, however: Elements with column-based classes ("onecolumn", "twocolumn," and so on) should only be direct children of an element with the "beams" class. Nesting them any deeper in the DOM may cause the design to break.

###Making Video Responsive

We should also mention a few words on making video players on your site's pages responsive.  This is possible in Beams 0.1.1 thanks to the jQuery plugin Fitvids.js. 

To achieve fluid-width videos in your pages' design, you'll need to target the videos' container with `fitVids()`.

```html

  <script src="js/jquery-2.1.1.min.js"></script>
  <script src="js/jquery.fitvids.js"></script>
  <script>
  $(document).ready(function(){
    // Target your .container, .wrapper, .post, etc.
    $("#thing-with-videos").fitVids();
  });
</script>
```

This will wrap each video in a `div.fluid-width-video-wrapper` and apply the necessary CSS. After the initial Javascript call, it's all percentage-based CSS magic. The plugin will elegantly resize embedded video players for YouTube and Vimeo, as well as self-hosted video using the HTML5 `<video>` tag.

**Add Your Own Video Vendor**
Have a custom video player? We now have a `customSelector` option where you can add your own specific video vendor selector (_mileage may vary depending on vendor and fluidity of player_):

```javascript
  $("#thing-with-videos").fitVids({ customSelector: "iframe[src^='http://mycoolvideosite.com'], iframe[src^='http://myviiids.com']"});
  // Selectors are comma separated, just like CSS
```

_Note:_ This will be the quickest way to add your own custom vendor as well as test your player's compatibility with FitVids.

**Ignore With Class**
Have a video you want FitVids to ignore? You can slap a class of `fitvidsignore` on your object or container and your video will be displayed as it is defined.

If you'd like to add a custom block to ignore FitVids, use the `ignore` option.

```javascript
  $("#thing-with-videos").fitVids({ ignore: '.mycooldiv, #myviiid'});
  // Selectors are comma separated, just like CSS
```


###Other Design-Related Matters

Beams 0.1.1's default design is spread across four stylesheets -- one that has the basic layout, including typography and the responsive grid; one that includes media queries; one for popup ads; and one that's blank for users to write custom styles for their particular projects.

The layout.css file, which is essentially the workhorse, includes few specialized classes to quickly cover certain effects that Web designers frequently use. These can be applied to any element in a project's HTML as a shortcut to get the desired visual effect. 

A complete list of these classes:

-*"unpadded"*: Sets element's padding at zero.
-*"padded-top"*: Adds 30px of padding at the top of the elment.
-*"padded-bottom":* Adds 30px of padding at the bottom of the element.
-*"no-margin":* Sets element's margin at zero.
-*"margin-top":* Adds a 30px margin at top of the element.
-*"margin-bottom":*Adds a 30px margin at bottom of the element.
-*"centered":* Centers a child element inside a beam.

###Ad Display & Metrics

Beams 0.1.1 includes simple jQuery plugins to implement display of interstitial ads in your project, with tracking of views using Google Analytics.

By default, Beams displays a responsive interstitial full-page ad upon initial site visit by a visitor to a page, then sets a cookie so as not to bug the user further.

To take advantage of the software's ad-display features, you'll need to make sure your pages have links to the appropriate scripts and CSS files in the Beams folders labeled "js" and "css." These links are included in the header of Beams 0.1.0's default "index.html" file, as follows:

```
<link rel="stylesheet" href="css/ads.css">
<script src="js/ads/jquery.interstitial.min.js" type="text/javascript"></script>
<script src="js/cookies/jquery.cookie.js" type="text/javascript"></script>
```
Of course, you can also remove those links from any pages that you *don't* want to include ads. In that case, users will view the content ad-free (and uninterrupted). 

If you do want to show ads on a page, include the following statement somewhere in its html, either in the the header or as an inline script in the body.

```
  if (!$.cookie('Ad_View')) {
    $().interstitial('open', {
      'url' : 'popup.html'
    }); 

    //Create a cookie
    
    $.cookie('Ad_View', '1', { path: '/' })

    };
```
Again, this will cause the interstitial to pop up once per site visit. You can edit the ad's content in the file labeled "popup.html." To change the design of the ad, edit the file "ads.css," in the CSS subfolder.

###Adding Google Analytics 

Most media-rich, content-heavy sites monetize through advertising. That's why we thought it was important to include some sort of analytics integration in Beams. 

Specifically, Google Analytics was the obvious candidate to start with considering how popular it is aroudn the web. This code snippet illustrates how the platform allow you to set up custom event trackers for ad clicks or similar events... 

	<a href=”” onClick=”_gaq.push(['_trackEvent', 'External Link', 'Twitter Link', 'Follow Us - Words']);”>Follow Us</a>

Where the labels used correlate to the following breakdowns in Google Analytics:

- Category (required), Action (Required), Label 1 (Optional), Label 2 (Optional, not included in example)

- With all this in mind, here are a few sample tracking scripts that would work with the Beams Ads plugin...

An event to track clicks on the "close link" for the interstitial would look like this:

	<a href=”tktktk” onClick=”_gaq.push(['_trackEvent', 'Sponsorships', 'Interstitial Closed', 'Sponsor1']);”>Follow Us</a>

An event to track a clickthrough on a sponsor link:

	<a href=”tktktk” onClick=”_gaq.push(['_trackEvent', 'Sponsorships', 'Sponsor Link Clickthrough', 'Sponsor1']);”>Follow Us</a>

You'll see sample trackers on the demo html pages we've included in Beams Ads. For a more detailed explainer about event tracking, I'd recommend the blog post at http://www.koozai.com/blog/analytics/how-to-track-clicks-on-a-link-in-google-analytics-7721/

###A Few Other Ad-Related Pointers

- Main function of the included CSS file is to make ad display responsive across different-sized screens. But it's very minimal otherwise, leaving a lot of flexibility to customize ads for your sponsors' particular needs.

- Structure of folders here is similar to the Beams Front End module, so you can just drop the ad-related files in there as needed, if you're using Beams Front End for your UX.

Finally, we must tip our hats to Brett DeWoody (http://github.com/brettdewoody) and Klaus Hartl (https://github.com/carhartl), whose previous open-source work on interstitials and cookies, respectively, provided much of the foundation for our ad features. Essentially, we've mashed up those two guys' projects, then added in responsive design and Google tracking.



<p align='center'>* * * * *</p>




That's it for now. Again, we're eager to have folks start using Beams, give their feedback, and participate in its development as a free project under MIT license. Our GitHub page is a great way to do that (http://github.com/roscoelabs), and we also invite folks to join the Beams Working Group listserv at http://bit.ly/1rF4j0G

At Roscoe Labs, we have a mantra: "Open media. Open communities." We really see Beams as a natural outgrowth of that broader idea. If you agree it's worth pursuing, please join us!

Best,

Peter McKay  
Co-Founder/Chief Product Officer  
Roscoe Labs  
Twitter: @peteramckay  
