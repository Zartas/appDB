AppDB Code release
------------------

REQUIREMENTS:
PHP 5.3 or higher
The Hydrogen PHP library, available at http://www.hydrogenphp.com .  This is the only library not included in this distribution, but
you can download it for free and plug it in.  Note that it uses 'Alpha 1' and that future versions may not work with this code.

NOTES:
This code is in various stages of disarray.  The first version was rushed and messy; this version is a middle-stage in getting it
flipped over into cleaner, more efficient code.  As a result, much of this code is either horrible or pristine, with very little
middle-ground.

Beyond this text file, this code is entirely unsupported, use-at-your-own-risk, figure-it-out-yourself.  You may have to modify MySQL
to allow indexing of 4+ letter words in FULLTEXT blocks.  You'll need to copy the configuration files in /config without 'sample' in
them, and customize.  You may have to dig through the code to find out what, exactly, some of the configuration options actually do.
You may have to retrieve your own product icons and screenshots, because that's a few gigs of data that I'm totally not trying to
serve out.  I didn't even back those up before I let the server expire, so please don't ask for it.

These things are all on you, if you choose to use the code.  I am not liable for any consequence of using this code, whether it results in
technical issues or legal issues.  This code is being offered ONLY as an educational resource.  Someone should be able to learn from
the time I put into this.

Note that, because this project was stopped during development, there are features alluded to in the code and entire database columns
that are never used and not programmed.  It's a bit confusing at first, but you can take a look at the DB schema in
/utilities/install.php and deduce what can be removed.  This code is also FULL of quirks and bugs.  Well maybe not 'full', but
there's a few.  Have fun, if you decide to hunt them down.

There are maintenance utilities in /utilities.  They're messy and rushed, but they work.  If you're using any form of the old AppDB
database dump, you may need to run correcttables to keep things clean.

Again, I relinquish all responsibility for this code.  It's here for people to learn from.  If you use it, any and all consequences
are yours.

Cheers :)
Kyek

PS: I forgot to mention -- since I'm keeping Appulo.us and the rights to the logo and all of that branding-type stuff, anything with
the logo has been overlaid with 'SAMPLE'.  If you're just learning from the code, none of that will matter.  If you're trying to take
this to launch some clone site somewhere, then you'll need to come up with your own site name and logo.  Not that I condone that,
because I don't.  I just know that some of you will try regardless.
