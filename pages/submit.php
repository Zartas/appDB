<?php
	use appdb\models\AppLinkModel;
	use appdb\usersession\UserSession;
	
	if (!class_exists('hydrogen\config\Config', false))
		die("Quit trying to hack my damn code.");
	
	$allowSubmit = false;
	$alm = AppLinkModel::getInstance();
	if (UserSession::getUserBean() != false)
		$submitCount = $alm->countLinksBySubmitterID(UserSession::getUserID());
	else
		$submitCount = 0;
	if ($submitCount > 0 || (isset($_GET['bypass']) && $_GET['bypass'] == 'true'))
		$allowSubmit = true;
	$sawWarn = isset($_GET['sawmsg']) ? $_GET['sawmsg'] == 'true' : false;
?>
<!--[PAGETITLE]>Submit an App<[/PAGETITLE]-->
<div id="page_submit"<?php if ((UserSession::getPermission('submit_new_itunes_apps') && !$allowSubmit) || !$sawWarn) echo ' class="staticpage"'; ?>>
	<h1 id="pagetitle">Submit an App</h1>
	<?php
		if (UserSession::getPermission('submit_new_itunes_apps') && $allowSubmit && $sawWarn) {
	?>
	<form id="appsubmissionform" action="?page=jscript" method="POST">
		<div id="itunesblock">
			<div class="appblock">
				<div class="appblock_icon">&nbsp;</div>
				<div class="appblock_infoblock">
					<span class="appblock_name"></span>
					<span class="appblock_company"></span>
					<span class="appblock_category"></span>
					<span class="appblock_version"></span>
				</div>
			</div>
			<div id="itunesfield">
				<div class="validitylabel">
					<label for="itunesurl" class="itunesurl">iTunes Store URL</label>
					<span id="itunesvalid">&nbsp;</span>
				</div>
				<input type="text" name="itunesurl" id="itunesurl" class="textfield" />
			</div>
		</div>
		<div id="fullform">
			<div id="versionrow">
				<div id="versionfield">
					<div id="versionselect">
						<label for="versionsel" class="versionsel">Version</label>
						<select name="versionsel" id="versionsel">
							<option value="unknown">Unknown</option>
						</select>
					</div>
					<div id="versiontext">
						<label for="versionother" class="versionother">Other</label>
						<input type="text" name="versionother" id="versionother" class="textfield" />
					</div>
				</div>
				<div id="crackerfield">
					<div id="crackerselect">
						<label for="crackersel" class="crackersel">Cracker</label>
						<select name="crackersel" id="crackersel">
							<option value="unknown">Unknown</option>
							<option value="me">Me</option>
							<option value="other">Other</option>
						</select>
					</div>
					<div id="crackertext">
						<label for="crackerother" class="crackerother">Other</label>
						<input type="text" name="crackerother" id="crackerother" class="textfield" />
					</div>
				</div>
			</div>
			<div id="linksblock">
				<div class="linkrow">
					<div class="linkblock">
						<label for="link1" class="link">Download link 1</label>
						<input type="text" name="link1" id="link1" class="textfield" />
						<span class="linkerror"></span>
					</div>
					<div class="typeblock">
						<label for="linktype1" class="linktype">Package type</label>
						<select name="linktype1" id="linktype1" class="typeselect packtype">
							<option value="app">APP</option>
							<option value="ipa" selected="true">IPA</option>
							<option value="unknown">Unknown</option>
						</select>
					</div>
				</div>
			</div>
			<div id="addlinkblock">
				<span>Add another download location</span>
			</div>
			<div id="submitblock">
				<input type="submit" value="Submit Application" id="submitbutton" />
			</div>
		</div>
	</form>
	<?php
		}
		else if (UserSession::getPermission('submit_new_itunes_apps') && !$allowSubmit) {
	?>
	<div id="submitnotes">
		<h3>Before you get started, please read over these quick points:</h3>
		<h2 class="pheading">ABSOLUTELY NO PASSWORD-PROTECTED FILES</h2>
		<p>If downloading the file requires a password, or if the downloaded archive itself requires a password to be extracted, that link is not allowed here.  But you're welcome to re-package and re-upload these files so that they do not require a password!  Repeat violators will be banned.</p>
		<h2 class="pheading">Version numbers are important</h2>
		<p>If you don't know the version number for an application you can submit it as 'Unknown', but please do that as a last resort ONLY.  Accuracy is very important so that we can track updates.</p>
		<h2 class="pheading">Give credit where credit is due</h2>
		<p>If you're not aware who the cracker is for a particular release, you can leave that field blank -- however, please do your best to find out and give credit.  If there's evidence of credit stealing or intentionally not giving credits, you may be banned.</p>
		<p class="continuelink"><a href="?page=submit&bypass=true">Continue to the submission form</a></p>
	</div>
	<?php
		}
		else if (!$sawWarn) {
	?>
	<div id="submitnotes">
		<h3>STUFF YOU SHOULD KNOW:</h3>
		<h2 class="pheading">Advertisers and Credit-stealers get banned</h2>
		<p>If you're using the 'cracker' field to advertise your own website, it will be changed or you'll be banned entirely.  Appulous isn't here for free advertising.  If you're interested in advertising on the site, contact the site owner.</p>
		<h2 class="pheading">Post a text file, get insta-banned</h2>
		<p>A handful of people have been posting links to text files, linking to ipas hosted at services that pay you for downloads.  These people have been getting banned with all their links deleted.  We've recently upgraded Appulous to lend itself to faster detection of those who try to make money off of this site.  Now, in addition to deleting such links and banning accounts, accounts will be reported to the filesharing sites so they can cancel payments.  We don't take kindly to people who try to trash our site for their own profit.</p>
		<p class="continuelink"><a href="?page=submit&bypass=true&sawmsg=true">Aye aye, Cap'n!</a></p>
	</div>
	<?php
		}
		else if (!UserSession::getUserBean())
			echo '<div id="nosubmit"><span class="formmessage formfailure">You must be logged in to submit applications.</span></div>';
		else
			echo '<div id="nosubmit"><span class="formmessage formfailure">You do not have permission to submit iTunes applications.</span></div>';
	?>
</div>