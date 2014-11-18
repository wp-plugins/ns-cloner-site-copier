=== NS Cloner - Site Copier ===
Contributors: neversettle
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=53JXD4ENC8MM2&rm=2
Tags: automate, duplicate, copy, copy site, copier, clone, clone site, cloner, multisite, network, subdomain, subdirectory, subfolder, template
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ALL NEW V3! The NS Cloner saves multisite admins and developers TONS of time by cloning existing sites in a multisite network to a completely new site in a few seconds.

== Description ==

This plugin ONLY works with WordPress Multisite, will NOT work in single site mode, and MUST be Network Activated. You will find it's menu in your network administration dashboard (wp-content/network)

This is by far the easiest, fastest, and most user-friendly way you will ever create fully configured sites on your multisite networks. As with everything we do, Never Settle is fanatical about simplifying user tasks to an absolute bare and joyful minimum without sacrificing the complex functionality going on behind the scenes. You will not find another site cloner that comes anywhere close to how easy this is to use.

The NS Cloner will take any existing site on your WordPress multisite network and clone it into a new site that is completely identical in theme & theme settings, plugins & plugin configurations, content, pictures, videos, and site settings. **Everything** is preserved and intelligent replacements are made so that the new site settings reflect your choices for the name and title of the new site as well as other automated background housekeeping to make sure the new site works exactly the same way as if you had taken the time to set it all up manually.

= ALL NEW Features with V3 =
* Advanced validation and preemptive issue detection
* Robust system configuration and action logging to help with troubleshooting
* All new Custom Add-on Architecture
* All new hook-rich cloning pipeline for developers to extend
* All new dynamic, responsive admin interface

If you want even more functionality, [check out our Add-ons and Bundles](http://neversettle.it/ns-cloner-add-ons-features)!

= Standard Precautions and Notes =
* Unlike other similar tools, the NS Cloner supports cloning the main root site at ID=1! But please be especially careful with this feature. Multisite plugins like BuddyPress add tables at the main (wp_) level of the database. There are also several global tables that apply to the network and NOT to the core site. The Cloner automatically excludes these global tables out of the gate so that they don't get cloned to all your new sites which will have wp_ID_ as a prefix instead of wp_. But tables for other network level plugins that don't apply to the clone could still get coppied by the Cloner due to its automation and inability to be aware of the table structure of every plugin out there. Our Table Manager add-on is perfect for unique scenarios where you need total table-level cloning control.
* We always try to help, but we cannot promise support to users for this Free version, especially related to cloning the root site do to the potential complexities involved from environment to environment.
* We have used the NS Cloner on production systems for years without issues. That doesn't mean your scenario won't find some new condition that could cause you some headaches. Unlikely, but always possible. We recommend getting familiar with it on a test system before you deploy it to a critical network.
* And for the love - backup your data. This plugin operates at the database level to work its magic. We've run it hundreds of times on our own sites and client sites, and tested it thoroughly. It's safe. But don't take our word for it.

= Typical Workflow for using the NS Cloner =
1. Set up 1 or more "template" sites exactly the way you want your clones to start out
1. Go to your Network Dashboard > NS Cloner
1. Select the "template" site you that want to clone, type the name of the new site, and put in it's Title
1. Clone Away!

Yes, it really is that easy.

= Primary Use Cases =
* Developers who host and manage multiple client sites in their own multisite environment - this will allow you to rapidly roll out new baseline sites with all your favorite standard plugins and configurations in place - no more tedious manual repetitive entry.
* Organizations which provide "member" sites and want to be able to reduce the site spin up time to almost nothing.
* Affiliates that host numerous sites through Multisite and are looking for a way to increase reach and decrease deployment times. 
* Designers who want to be able to create several versions of sites to test and play with different theme designs in parallel without having to re-install all the same plugins and base themes over and over.

== Features ==

= Some of the NS Cloner highlight features: =
1. Copies an entire site in seconds
1. Works in subdomain or subdirectory mode
1. Copies ALL theme and plugin settings
1. Copies ALL content and custom post types
1. Copies ALL media files from original site
1. ** Advanced validation and preemptive issue detection
1. ** Robust system configuration and action logging
1. ** Custom Add-on Architecture
1. ** All new hook-rich cloning pipeline
1. ** All new dynamic, responsive interface

(** New functionality in V3)
If you want even more functionality, [check out our Add-ons and Bundles](http://neversettle.it/ns-cloner-add-ons-features)!

== Installation ==

1. Log in to your WordPress network as a multisite super admin and go to /wp-admin/network
1. Use the Dashboard > Plugins > Add New tools to install NS Cloner from the WP.org repository or install by uploading the zip file
1. Network Activate the NS Cloner through the 'Plugins' menu in WordPress
1. Access the NS Cloner from its main menu on the Network Dashboard (/wp-admin/network)

== Frequently Asked Questions ==

= How do I contact Support, provide Feedback, or make a Feature Request? =
You can browse our Knowledge Base, add or vote on Feature Requests, or contact us with an issue at <a title="Never Settle Support and Feedback" href="http://support.neversettle.it" target="_blank">http://support.neversettle.it</a>

= Does the NS Cloner work on subdomain networks as well as subfolder networks? = 
YES!

= Why do I get a white screen after cloning a site? = 
Usually this means that the clone operation did not complete successfully. The most common cause for this is a script timeout. By default, PHP script execution for a single script is set to 30 seconds. This might not be enough time for larger sites with numerous posts, pages, and users to complete cloning - especially since the Cloner runs advanced search and replace operations against every table cloned to the new site to make sure that it reflects the new site url and title throughout all its data. Try increasing the max_execution_time in php.ini or wherever your host supports updating PHP configuration settings. You can <a title="Cloning White Screen Issue" href="http://support.neversettle.it/knowledgebase/articles/379601-white-screen-or-404-and-blank-site-after-cloning" target="_blank">read more detailed troubleshooting tips for this issue</a> on our support site.

== Screenshots ==

1. All new dynamic, responsive, add-on aware UI
2. All new Add-on manager

== Changelog ==
= 3.0.4 =
* Added one-click cloning from Manage Sites page
* Added pre WP 3.7 compatibility
* New search selector for source sites
* Revamped logs for better utility/readability
* Centralized ns_cloner_addon class for maintability

= 3.0.3.1 =
* Fixed bug introduced in 3.0.3 affecting subdirectory mode

= 3.0.3 =
* Added exception for subsite names to include dashes
* Updates to support new Registration Templates add-on

= 3.0.2 =
* Fixed conflicts with some other multisite plugins that were triggering validation errors when attempting a clone
* Raised number of sites shown by default in "Select Source" dropdown from 100 to 1000 and made parameters for that query filterable 

= 3.0.1 =
* Fixed www vs. non-www issue on subdomain installs that were using www and generating sites like site.www.domain.com
* Added robust new detection system for identifying WP version and uploads location even under abnormal edge cases
* Added advanced validation and preemptive issue detection
* Added robust system configuration and action logging to help with troubleshooting
* Added All new Custom Add-on Architecture
* Added All new hook-rich cloning pipeline for developers to extend
* Added All new dynamic, responsive admin interface

= 2.1.4.9 =
* Fixed several small bugs reported to support that impacted specific scenarios, configurations, and plugin combinations.

= 2.1.4.8 =
* Fixed bug that was causing UTF-8 and DB character encoding issues in some environments. Primarily affected non-English sites.

= 2.1.4.7 =
* Fixed critical bug that prevented user roles from getting cloned when cloning the ROOT site only

= 2.1.4.6 =
* Fixed critical bug that was causing incompatibilities with some plugins that install their own tables to the database and in some cases the cloning operation was leaving a single custom table behind in the clone. This primarily affected Calendar plugins or other plugins where the table name began with higher letters (a, b, c) after the prefix like wp_a*, wp_b*, wp_c*

= 2.1.4.5 =
* Added support for cloning the root site with ID 1 (YAY! PLEASE SEE STANDARD PRECAUTIONS AND NOTES ON DESCRIPTION PAGE)
* Added support for WP Multisite when installed in a subdirectory rather than at the root of a domain (there were previously issues in this scenario)
* Added validation to enforce lowercase and only allowed characters in the site name field
* Added validation to enforce replacement rules that aren't always obvious (you don't want the old site name to be contained in the new site domain or the cloner's automated data replacement will corrupt your new clone's data)
* Fixed permalink bug in subdirectory mode
* Updated the way the status is returned after cloning to fix issues where the status exceeds URL length restrictions
* [EXPERIMENTAL] Added support for the ThreeWP Broadcast plugin based on user contribution (thank you John @ propanestudio.com and Aamir!)
* Many other small tweaks, updates, and fixes

= 2.1.4.4 =
* Enhanced media file copy handling from 2.1.4.3

= 2.1.4.3 =
* Added better media file copy handling in cases where themes or plugins alter wp_upload_dir() and it is returning bad paths

= 2.1.4.2 =
* Fixed bug reported by Christian (Thank you!) where some upload file paths containing the same numbers as site IDs were getting mangled

= 2.1.4.1 =
* Fixed 2.1.4 to make file copies compatible with the new uploads structure in native WP 3.5 installs
* ANNOUNCING NS Cloner Pro is now Available [HERE](https://neversettle.iljmp.com/1/ns-cloner-pro)

= 2.1.4 =
* Fixed bug in 2.1.3 that caused file copies to fail in some cases where the target folders already existed

= 2.1.3 =
* Fixed bug in 2.1.2 that forced subdirectory mode

= 2.1.2 =
* Added Auto-detect of Multisite mode and Subdirectory site support!
* Added Automatic Copy of all media files in blogs.dir/##
* Fixed some image loading fails in certain scenarios

= 2.1.1 =
* First public release

== Upgrade Notice ==

= 2.1.1 =
* First public release

= 2.1.3 =
* Fixed bug in 2.1.2 that forced subdirectory mode - if you updated to 2.1.2 please update to 2.1.3 immediately.

= 2.1.4 =
* Fixed bug in 2.1.3 that caused file copies to fail in some cases where the target folders already existed. Update to correct the issue if affected.

= 2.1.4.1 =
* Fixed 2.1.4 to make file copies compatible with the new uploads structure in native WP 3.5 installs. This should correct issues with the media file copes! Please update ASAP.

= 2.1.4.5 =
* Added validation to prevent unsafe values for certain fields like site name
* Updated deprecated function calls and fixed several critical bugs affecting certain scenarios like when WP Multisite is installed in a subdirectory (not to be confused with simply running in subdirectory mode)

= 2.1.4.6 =
* Fixed critical bug that was causing incompatibilities with some plugins that install their own tables to the database and in some cases the cloning operation was leaving a single custom table behind in the clone. This primarily affected Calendar plugins or other plugins where the table name began with higher letters (a, b, c) after the prefix like wp_a*, wp_b*, wp_c*

= 2.1.4.7 =
* Fixed critical bug that prevented user roles from getting cloned when cloning the ROOT site only

= 3.0.1 =
* All new V3!

= 3.0.3.1 =
* Fixed bug introduced in 3.0.3 affecting subdirectory mode

= 3.0.4 =
* Added one-click cloning from Manage Sites page
* Added pre WP 3.7 compatibility
* New search selector for source sites
* Revamped logs for better utility/readability
* Centralized ns_cloner_addon class for maintability
