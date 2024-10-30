=== Plugin Name ===
Contributors: chuhpl, maniacalv
Tags: lending, borrow
Requires at least: 4.7
Tested up to: 6.1.1
Stable tag: trunk
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

LendingQ is a simple way to manage and lend out items without worrying about using a large ILS.

== Description ==

LendingQ allows you to specify multiple locations and item types, and then add items to your stock.

Non-admin users can fill out a Hold on an item from a specific item type and location.

This creates a Check Out list, ordered by date.

Users can be marked as contacted, and items have a time to lend. Overdue items are listed.

The Check In list allows non-admin users to check devices back in, and even mark them as lost, stolen or broken. Admin users can reenable these items once fixed or replaced.


== Frequently Asked Questions ==

= Why doesn "something" work? =

This is an alpha release that is being tested to work out the kinks!

== Screenshots ==

== Changelog ==
= 1.0 =
* BUGFIX: Fixed the sorting for the order check out list so it's correct
* BUGFIX: Changed the display in several places from post title to the Item Name

= 0.98 = 
* BUGFIX: If you try to add a hold on an item type that isn't in the stock for that location, the hold will error and go to draft.

= 0.97 =
* BUGFIX: Check out list was in reverse order!
* BUGFIX: Made it so that if you draft a ticket with a bad entry, you can still edit the waiting date and time, if applicable.

= 0.96.3 = 
* Added in Bulk Delete in stock to make pagination reset work again.

= 0.96.2 = 
* Fixed date (changed from date_i18n to date) on the check out page to correct timezone.
* Changed page to 1 when you activate a filter on the posts page.

= 0.96.1 = 
* Fixed the check in page to work in PHP 5 (array_column wasn't working with an array of post objects)

= 0.96 = 
* Added the ability to filter the Check Out page by location.

= 0.95.1 = 
* Added in text-domain functions
* Replaced all instances of array() with []

= 0.95 =
* Deactivated the test holder Dashboard Widget.
* Fixed the alternating rows CSS for Check in and Check out lists.
* Removed an extra quote in the option list for filters.
* Added the post status and made it sortable to the All Holds list.
* Removed unwanted test files.

= 0.94 = 
* Reorganized all functions alphabetically.

= 0.93 =
* Renamed all globals to prefix with LENDINGQ_

= 0.92 = 
* Fixed external include of validate Jquery library.
* Added sanitizing to all REQUESTs.

= 0.91 =
* First public Alpha version.

== Upgrade Notice ==
