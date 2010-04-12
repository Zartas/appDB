<?php use hydrogen\config\Config; ?>
<!--[PAGETITLE]>Upcoming Changes<[/PAGETITLE]-->
<div id="page_changes" class="staticpage">
	<h1 id="pagetitle">Upcoming Changes</h1>
	<h2 class="pheading">Top priority</h2>
	<ul>
		<li>Internationalization!  If you can help translate the text on <?php echo Config::getVal('general', 'site_name'); ?>, check <a href="http://hackulo.us">Hackulous</a> for updates on when we can use your skill!</li>
		<li>Implement a link reporting system, where users can inform <?php echo Config::getVal('general', 'site_name'); ?> of links that are incorrect or no longer exist.</li>
		<li>Implement a link counter, which can judge the popularity of a submitted application by how many times its links were clicked on in total.  Once this is underway, results can be listed by popularity and the link-removal system can be automated.</li>
		<li>Give the links area for each application a complete overhaul.  Show which domain each link is to so you aren't forced to hover over each and look at the status bar.</li>
		<li>Implement the Link Certification System, allowing volunteers to mark links that they've certified to be working and correct.</li>
	</ul>
	<h2 class="pheading">Also in the works</h2>
	<ul>
		<li>The ability to edit your own links and manual submissions, change your password, retrieve your forgotten password, and all that other user account stuff.</li>
		<li>A reviews system.  Reviews in Apple's App Store are rarely relevant and can't be trusted, and so we're planning to have our own reviews process.  Posting reviews will require an account on <?php echo Config::getVal('general', 'site_name'); ?>.</li>
		<li>Back-button functionality.  Unfortunately this is still impossible to implement in all web browsers at once, but the browser's back button should function as though each page of apps or each viewed application were a separate page you could go back and forward to, rather than an AJAX call.  Note that this will not affect current AJAX functionality.</li>
		<li>A better design.  This site's design was thrown together very quickly to allow for speedy development of this web application.  It is very functional, but not very suave.  Note that this is not a high priority, as <?php echo Config::getVal('general', 'site_name'); ?> believes the functionality and features provided by the other listed goals is more important.</li>
		<li>A "Watchlist" feature, where a single click will add an app to your watch list.  From there, you will be informed when it's updated.</li>
		<li>Add a manual submission form so that applications not currently available in the iTunes store can be added.</li>
		<li>Other unlisted surprises :)</li>
</div>