<?php use hydrogen\config\Config; ?>
<!--[PAGETITLE]>Approved Domains<[/PAGETITLE]-->
<div id="page_domains" class="staticpage">
	<h1 id="pagetitle">Approved Domains</h1>
	<h2 class="pheading">Approved what?</h2>
	<p><?php echo Config::getVal('general', 'site_name'); ?> has a level of quality to maintain, and we pride ourselves on how easy and useful this site is.  However, there are many file-sharing services out there whose quality and level of service are so abysmal that we can't even imagine forcing <?php echo Config::getVal('general', 'site_name'); ?> users to download from there.  Unfortunately, some of our greedier users started posting links to these sites due to payment benefits they offered.  Their greed was making <?php echo Config::getVal('general', 'site_name'); ?> worse for everyone!  So we've locked down the links you can submit to a set list of sites.</p>

	<h2 class="pheading">I know a good site that's not in this list!</h2>
	<p>Fantastic!  Contact the <?php echo Config::getVal('general', 'site_name'); ?> author to have it added :)</p>

	<h2 class="pheading">Approved file-sharing sites</h2>
	<p>
		<ul>
			<?php
				foreach (Config::getVal('domains', 'allowed') as $aldm)
					echo "<li><a href=\"http://$aldm\">$aldm</a></li>\n";
			?>
		</ul>
	</p>
</div>