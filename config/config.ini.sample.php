<?php namespace hydrogen\config; class ConfigINI { static $ini = <<<'EOF'
;*************************************************************************
; AppDB Configuration
;*************************************************************************

[general]
site_name = "Example AppDB"
default_page = applist
disable_search = 0
; If 1, app details (incl. graphics) will be updated with every single link
; submission.  If 0, app details will only be updated when a new version
; is submitted.
update_app_every_submit = 0

[paths]
icon_path = /absolute/path/to/appimages/icons
screenshot_path = /absolute/path/to/appimages/screenshots

[urls]
base_url = "http://domain.com/appdb"
icon_url = "http://domain.com/appimages/icons"
screenshot_url = "http://domain.com/appimages/screenshots"

[rewrite]
;; The following is the filename of the RSS url to give when no options are passed.  If blank, this will be
;; 'rss.php'.  Note that any rewrites will have to be set up manually on the server in addition to in this file.
rss =
;rss = "feed.xml"

;; This is the rewrite for just the type option, when no other options are passed.  If blank, this will be
;; 'rss.php?type=%TYPE%'.  Use %TYPE% in place of where the type should go.
rss_type =
;rss_type = "feed-%TYPE%.xml"

;; Normally RSS feeds with options are in the format BASE_URL/rss.php?results=15&sort=newvers&cat=0&filter=&type=html. If
;; the following is not blank, the URL will be rewritten to its specifications.  Example:
;; rss_options_rewrite = "rss/%RESULTS%/%SORT%/%CAT%/%FILTER%/feed-%TYPE%.xml"
;; will produce the following URL in links (assuming base URL is [http://example.com/appdb/]):
;; http://example.com/appdb/rss/15/newvers/0/*/feed-html.xml
rss_options =
;rss_options = "rss/%RESULTS%/%SORT%/%CAT%/%FILTER%/feed-%TYPE%.xml"

[domains]
allowed[] = 2shared.com
allowed[] = 4shared.com
allowed[] = adrive.com
allowed[] = appscene.org
allowed[] = badongo.com
allowed[] = endlessapps.net
allowed[] = getapp.info
allowed[] = ifile.it
allowed[] = ipauploader.com
allowed[] = mediafire.com
allowed[] = zippyshare.com
allowed[] = zshare.net

friendly[] = 2shared.com
friendly[] = 4shared.com
friendly[] = appscene.org
friendly[] = crapscene.org
friendly[] = crazyapps.net
friendly[] = depositfiles.com
friendly[] = endlessapps.net
friendly[] = getapp.info
friendly[] = ipauploader.com
friendly[] = megaupload.com
friendly[] = rapidshare.com
friendly[] = sendspace.com
friendly[] = zippyshare.com

[recaptcha]
public_key = 
private_key = 

[mint]
enabled = 0
url = "/mint/";
install_path = "/absolute/path/to/mint/";

[cache]
engine = "Memcache"

[database]
engine = "MysqlPDO"
host = "localhost"
port = 3306
socket = 
database = "appdb"
username = "appdb"
password = "password"
table_prefix = "appdb_"

[recache]
unique_name = 'XYZ'

[semaphore]
engine = "Cache"

[errorhandler]
log_errors = 1

[log]
engine = TextFile
logdir = cache
fileprefix = "appdb_"
; 0 = No logging
; 1 = Log Errors
; 2 = Log Warnings & worse
; 3 = Log Notices & worse
; 4 = Log Info & worse
; 5 = Log Debug messages & worse
loglevel = 1

;*************************************************************************
EOF;
}?>