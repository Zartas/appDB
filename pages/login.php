<?php 
	use hydrogen\config\Config;
	$errormsg = false;
	$verifying = false;
	$logintitle = 'Log in';
	if (isset($_GET['action'])	&& $_GET['action'] == 'verify') {
		if (isset($_GET['code']) && strlen($_GET['code']) == 32) {
			$verifying = true;
			$logintitle = 'Log in to verify your account';
		}
		else
			$errormsg = "You followed an invalid verification link.  Please make sure to copy the entire URL to your address bar.";
	}
	echo "<script type=\"text/javascript\">\n\t\t\t\tvar VERIFYING = " . ($verifying ? 'true' : 'false') . ";\n\t\t\t</script>\n";
?>
<!--[PAGETITLE]>Log In<[/PAGETITLE]-->
<div id="page_register">
	<?php if ($errormsg) { echo '<span class="errormsg">' . $errormsg . '</span>'; } else { ?>
	<div id="logregblock">
		<div id="logblock"<?php if ($verifying) echo ' class="verifying"'; ?>>
			<span class="sectiontitle<?php if ($verifying) echo " verifying"; ?>"><?php echo $logintitle; ?></span>
			<span id="loginerror"></span>
			<form id="login" action="POST" method="/?page=jscript">
				<?php
					if ($verifying)
						echo '<input type="hidden" name="code" value="' . $_GET['code'] . '" />' . "\n";
				?>
				<div class="formrow">
					<label for="loginuser">Username</label>
					<input type="text" name="username" id="loginuser" class="textfield" value="" />
				</div>
				<div class="formrow">
					<label for="loginpass">Password</label>
					<input type="password" name="password" id="loginpass" class="textfield" value="" />
				</div>
				<div class="remembermerow">
					<input type="checkbox" name="rememberme" id="rememberme" value="rememberme" />
					<label for="rememberme">Remember me</label>
				</div>
				<div class="submitbox">
					<input type="submit" value="Log in" id="loginsubmit" />
				</div>
			</form>
		</div>
		<?php if (!$verifying) { ?>
		<div id="regblock">
			<span class="sectiontitle">Register</span>
			<span id="regerror"></span>
			<form id="register" action="POST" method="/?page=jscript">
				<div class="formrow">
					<label for="reguser">Username</label>
					<input type="text" name="username" class="textfield" id="reguser" value="" />
				</div>
				<div class="formrow">
					<label for="regpass">Password</label>
					<input type="password" name="password" class="textfield" id="regpass" value="" />
				</div>
				<div class="formrow">
					<label for="regpass2">Verify Password</label>
					<input type="password" name="password" class="textfield" id="regpass2" value="" />
				</div>
				<div class="formrow">
					<label for="regemail">E-mail</label>
					<input type="text" name="email" id="regemail" class="textfield" value="" />
				</div>
				<div class="captcharow">
					<div id="recaptcha"></div>
				</div>
				<div class="agreetermsrow">
					<input type="checkbox" name="agreeterms" id="agreeterms" value="agreeterms" />
					<label for="agreeterms">I agree to the <a href="?page=terms">Terms and Conditions</a>.</label>
				</div>
				<div class="submitbox">
					<input type="submit" value="Send me the verification E-mail" id="regsubmit" />
				</div>
			</form>
		</div>
		<?php } ?>
	</div>
	<div id="privacyinfo">
		<span class="sectiontitle">Privacy Info</span>
		<ul>
			<li>Registration is not needed to browse or download apps -- only to add them to our database.</li>
			<li>E-mail addresses are used only to protect against spammers, and as a last resort should <?php echo Config::getVal('general', 'site_name'); ?> need to inform its members of major changes.  We will not spam you, and you'll probably never get another E-mail from us after you verify your account.</li>
			<li>User information stored here is private and will not be given away, sold, or otherwise abused.  Passwords are stored in an encrypted hash, meaning no one, not even the site owner, will ever be able to read it.</li>
		</ul>
	</div>
	<?php } ?>
</div>