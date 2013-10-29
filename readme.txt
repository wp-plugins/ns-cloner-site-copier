=== NS Cloner - Site Copier ===
Contributors: neversettle
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=53JXD4ENC8MM2&rm=2
Tags: never settle, automate, duplicate, copy, copier, clone, cloner, multisite, nework, subdomain, template, developer
Requires at least: 3.0.1
Tested up to: 3.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The NS Cloner saves multisite admins LOTS of time by enabling them to clone existing sites in their network to a completely new site in a few seconds.

== Description ==

This is by far the easiest, fastest, and most user-friendly way you will ever create fully configured sites on your multisite networks. As with everything we do, Never Settle is fanatical about simplifying user tasks to an absolute bare and joyful minimum without sacrificing the complex functionality going on behind the scenes. You will not find another site cloner that comes anywhere close to how easy this is to use.

The NS Cloner will take any existing site on your WordPress multisite network and clone it into a new site that is completely identical in theme & theme settings, plugins & plugin configurations, content, pictures, videos, and site settings. **Everything** is preserved and intelligent replacements are made so that the new site settings reflect your choices for the name and title of the new site as well as other automated background housekeeping to make sure the new site works exactly the same way as if you had taken the time to set it all up manually.

If you want even more functionality, [check out our Pro version](https://neversettle.iljmp.com/1/ns-cloner-pro)!

= Standard Precautions and Notes =
* This plugin ONLY works on WordPress Multisite and is not for use on single site installations. 
* It now supports both subdomain and subdirectory mode! It will auto-detect and auto-render appropriate UI.
* It now supports cloning the root site at ID=1! But please be especially careful with this feature. Multisite plugins like BuddyPress add tables at the main (wp_) level of the database. There are also several global tables that apply to the network and NOT to the core site. We are supporting an exclusion on these global tables and BuddyPress tables out of the gate so that they don't get cloned to all your new sites which will have wp_ID_ as a prefix instead of wp_. But tables for other network level plugins that don't apply to the clone could still get coppied by the Cloner due to its automation and inability to be aware of the table structure of every plugin out there. 
* We always try to help, but we cannot promise support to users for this Free version especially related to cloning the root site do to the potential complexities involved from environment to environment.
* We have used the NS Cloner on production systems for months and months without issue. That doesn't mean your scenario won't find some new condition that could cause you some headaches. Unlikey, but always possible. We recommend getting familiar with it on a test system before you deploy it to a critical network.
* And for the love - backup your data. This plugin operates at the database level to work its magic. We've run it hundreds of times on our own sites and client sites, and tested it thoroughly. It's safe. But don't take our word for it.

= Typical Workflow for using the NS Cloner =
1. Set up 1 or more "template" sites exactly the way you want your clones to start out
1. Go to your Network Dashboard > Sites > NS Cloner
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
1. Incredibly simple 4 step process to copy entire sites in seconds
1. Works in subdomain or subdirectory modes and auto-detects which is in use
1. Copies ALL theme and plugin settings
1. Copies ALL content including custom post types and taxonomies
1. Copies ALL site settings and configurations 
1. Copies ALL media files uploaded to the original site
1. Intelligently replaces subdomain and subdirectory names to ensure that everything works in the context of the new site

== Installation ==

1. Log in to your WordPress network as a multisite super admin and go to /wp-admin/network
1. Use the built-in *Plugins* tools to install NS Cloner from the repository or Upload the `ns-cloner` directory to the `/wp-content/plugins/` directory
1. Network Activate the plugin through the 'Plugins' menu in WordPress
1. Access the NS Cloner tool in the Network Sites Menu

== Frequently Asked Questions ==

= Does the NS Cloner work on subdomain networks as well as subfolder networks? =

YES! We have added this functionality.

= When I click the "Clone Away" submit button, the new site is created, but the response generates a 404 page not found? =

Check with your host. They probably have an agreesive mod_security configuration and might need to add or modify some rules for you. For example, rule 1234234 needs to be present which allows dots in querystring parameters.

= When will the Pro version be available? =

Wait no longer! [It's here!](https://neversettle.iljmp.com/1/ns-cloner-pro)

= Can I clone the root site (ID=1)? =

Prior to 2.1.4.5 the answer was no. The tables for the root site are prefixed differently than all the other tables in sub sites and this structure doesn't lend itself to the same automation that is possible with ID > 1. 

HOWEVER!!! We put a lot of effort into this and have built checks and conditions for the most common scenarios, and now as of version 2.1.4.5 you CAN clone the root site with ID=1.

= Why do I get a white screen after cloning a site? =

Usually this means that the clone operation did not complete successfully. The most common cause for this is a script timeout. By default, PHP script execution for a single script is set to 30 seconds. This might not be enough time for larger sites with numerous posts, pages, and users to complete cloning - especially since the Cloner runs advanced search and replace operations against every table cloned to the new site to make sure that it reflects the new site url and title throughout all its data. Try increasing the max_execution_time in php.ini or wherever your host supports updating PHP configuration settings.

== Screenshots ==

1. Navigating to the Network Admin area
2. NS Cloner Pro and NS Cloner in the Sites Menu of Network Admin
3. The NS Cloner in all its simple, user-friendly glory

== Changelog ==
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
First public release

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