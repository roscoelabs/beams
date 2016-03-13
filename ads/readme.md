#Beams Ads

A simple, open plugin to implement display of interstitial ads on a website. For a demo, visit https://pmckay.com/demo/beams_ads


###Features

- Built in jQuery v2.1.0.

- Displays a responsive interstitial full-page ad upon initial site visit by a visitor to any page, then sets a cookie so as not to bug the user further.

- Includes boilerplate Google Analytics code that can be customized to track ad views, ad click-throughs, and other events for ads on your site.

###Usage

To implement the software's ad display features, first add the Beams Ads CSS and JavaScript files to your project. Then link to them in the header of any html page you want to show ads by adding links as follows:

```
<link rel="stylesheet" href="css/ads.css">
<script src="js/ads/jquery.interstitial.min.js" type="text/javascript"></script>
<script src="js/cookies/jquery.cookie.js" type="text/javascript"></script>
```

Then add the following statement somewhere on the html page, either in the the header or as an inline script in the body.

```
  if (!$.cookie('Ad_View')) {
    $().interstitial('open', {
      'url' : 'popup.html'
    }); 

    //Create a cookie
    
    $.cookie('Ad_View', '1', { path: '/' })

    };
```


###A quick tutorial on creating Google Analytics event trackers 

As many sites monetize through advertising, we thought it was important to include some sort of analytics integration in the Beams Ads plugin. 

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

###A few other pointers

Main function of the included CSS file is to make ad display responsive across different-sized screens. But it's very minimal otherwise, leaving a lot of flexibility to customize ads for your sponsors' particular needs.

Beams Ads was originally written for inclusion in Roscoe Labs' open, full-stack Beams framework for independent web publishing. Hence the name Beams Ads. 

Ad features are no longer officially supported by Roscoe Labs in the main Beams project, but Beams Ads code lives on as its own thing here.

Beams Ads is (and always will be) free to use under MIT license.

Finally, I must tip my hat to Brett DeWoody (http://github.com/brettdewoody) and Klaus Hartl (https://github.com/carhartl), whose previous open-source work on interstitials and cookies respective provided much of the foundation for this plugin. Essentially, I mashed up those two guys' projects, then added in responsive design and Google tracking of my own.

Cheers,

Peter McKay   
Co-Founder/Chief Product Officer   
Roscoe Labs      
