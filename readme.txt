=== Challonge ===
Contributors: zavaboy
Donate link: https://zavaboy.org/challonge-wordpress-plugin/
Tags: plugin, widget, shortcode, api, integration, embed, events, games, tournaments, matches, Challonge, brackets
Requires at least: 3.3
Tested up to: 4.9
Requires PHP: 5.5
Stable tag: trunk
License: MIT License
License URI: http://opensource.org/licenses/MIT

Integrates Challonge, a handy bracket generator, into WordPress.

== Description ==

Do you use [Challonge](https://challonge.com/ "Free online tournament management and brackets (single & double elimination, round robin, Swiss)") for your gaming or sport events? The Challonge plugin integrates your Challonge tournaments into your WordPress website so your users may easily see recent tournaments, their progress, and even sign up and participate.

= Features =

* Adaptive caching and background refresh allows your pages to load a lot faster than before! - **New in 1.1.6!**
* Only registered users may sign up to your tournaments.
* Likewise, your users may forfeit a tournament after signing up, but only before it begins.
* Participating users may report their own scores.
* Includes role capabilities. You will need a role management plugin to change who has these capabilities.
* Custom participant name templating.

= Languages =

* English
* Spanish (68%) - Thanks to Andrew Kurtis from [WebHostingHub](http://www.webhostinghub.com/) for translating!

= Requirements =

In order to use the API, you will need [cURL](http://php.net/manual/en/book.curl.php). Most PHP installations include cURL.

= Latest Information =

Keep up to date with upcoming release information on my website:

https://zavaboy.org/challonge-wordpress-plugin/

= Getting Started =

Before you start using this plugin, here's what you'll need:

* A [Challonge.com](https://challonge.com/) account. Registration is free.
* A valid [Developer API Key](https://challonge.com/settings/developer) so the Challonge plugin can talk with your Challonge.com account.

Once you have the Challonge plugin installed and activated on your website, you will need to enter your Challonge.com API key in 'Settings' > 'Challonge'. Once you have done that, you have unlocked the full power of this nice plugin.

= Shortcode =

You may use a shortcode in posts and pages to display a tournament or list out tournaments.

* **`[challonge]`** - This will list out all tournament brackets in your account, excluding all organizations.
* **`[challonge url="w4la9fs6"]`** - This will embed a tournament bracket. This may be any Challonge bracket, not just your own.
* **`[challonge subdomain="my_sub"]`** - This will list out all tournament brackets in the 'my_sub' organization. (eg: my_sub.challonge.com)
* **`[challonge url="w4la9fs6" theme="2" show_final_results="1" width="90%" height="600px"]`** - This is just a more customized version of the first shortcode.

If you have a tournament bracket within an organization, you will have to use the `subdomain` attribute along with the `url` attribute, like so:

**`[challonge url="w4la9fs6" subdomain="my_sub"]`**

Here's all the shortcode attributes available to you:

* **`url`** - The URL to a tournament.
* **`subdomain`** - The subdomain of the tournament URL or if no tournament URL is provided, the listing will be tournaments within the specified subdomain.
* **`theme`** - The theme ID you would like to use.
* **`multiplier`** - Scales the entire bracket.
* **`match_width_multiplier`** - Scales the width allotted for names.
* **`show_final_results`** - Display the final results above your bracket.
* **`show_standings`** - For round robin and Swiss tournaments, you can opt to show a table of the standings below your bracket.
* **`width`** - The width of the embedded tournament bracket.
* **`height`** - The height of the embedded tournament bracket.
* **`limit`** - Limit the number of returned results for tournament listings.
* **`allowusers`** / **`denyusers`** / **`allowroles`** / **`denyroles`** - A comma separated list of users or roles you would like to specifically allow or deny from viewing the tournament bracket or tournament listings.
* **`statuses`** / **`excludestatuses`** - A comma separated list of tournament statuses you would like to specifically show or hide from the tournament listings. All statuses: "Pending,Checking In,Checked In,Underway,Awaiting Review,Complete"
* **`listparticipants`** - List participants currently in the tournament. (Must be used with `url`) - **New in 1.1.6!**

= Widget =

To allow your users to signup and report their own scores, just add the plugin widget.

The widget has the following options:

* **Title** - The title of the widget, nothing special here. Defaults to 'Challonge'.
* **Subdomain** - The subdomain to list your tournaments from. (Optional)
* **Tournament Filter** - Only tournament names that match this filter will be listed. (Optional) This may be a simple wildcard filter, for example `My * Tournament` will match 'My Big Tournament' but not 'Your Big Tournament'. If you need a more robust filter, you may use Regular Expressions (PCRE) like so: `/My \d+(st|nd|rd|th) Tournament/i` will match 'My 3rd tournament' but not 'My Third Tournament'
* **Status Filter** - Only list tournaments with the selected statuses, unless none are selected.
* **Max tournaments listed** - The maximum number of tournaments that the widget will list. Defaults to 10.

= Integrating Challonge.com Tournaments =

Challonge.com tournaments may be easily setup to allow your WordPress users to signup and report scores. Here are a few things you should know when setting up your Challonge.com tournaments:

* Turning 'Host a sign-up page' on will allow your users to signup through the widget.
* In 'Advanced Options' > 'Permissions': Turning 'Allow participants with Challonge accounts to report their own scores.' on will allow your users to report their own scores through the widget.
* In 'Advanced Options' > 'Permissions': Turning 'Exclude this event from search engines and the public browsable index.' on will hide the tournament from the shortcode and widget tournament listings.

= Did You Know? =

If you run the same tournaments on multiple WordPress websites, your WordPress users will be tracked in your Challonge.com tournaments by their email address and login name, even if their display name differs. With this in mind, users may signup and report their score using either website. Also note, users who change their email address will lose access to any of their preexisting tournament signups.

Good luck!

== Installation ==

1. Upload the `challonge` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. You should add your Challonge.com 'Developer API Key' to 'Settings' > 'Challonge'.

== Frequently Asked Questions ==

= Why was this Challonge plugin made? =

At first, I needed a WordPress plugin to help with some tournaments I was involved with and nobody had made one or was working on one at that time, so I started making this plugin. Later, I no longer needed a plugin, but I already started the project and people have already noticed. After about a year of life distractions and general procrastination, I completed the first version.

= Do I need a Challonge.com account to use this plugin? =

Actually, no you don't. (Keep reading!) Without an account, you will only be able to embed tournament brackets into posts and pages with the shortcode, but you will not be able to get any tournament listings with the shortcode or in the widget. You will need a valid Challonge API key, which you can get easily with a [Challonge.com](https://challonge.com/) account, to use all the Challonge WordPress plugin has to offer. A Challonge.com account is free.

= I think I found a bug. What do I do? =

Although I do my best to avoid introducing bugs, especially with complexity growing every release, it gets increasingly difficult for me to squish them all before I make a release. That said, please report any bugs you come across in the [support forum](https://wordpress.org/support/plugin/challonge). I try to get to bug reports quickly depending on the severity. If I'm already aware of the bug, you will find it in the [upcoming release notes](https://zavaboy.org/challonge-wordpress-plugin/) on my website.

= Can you make the plugin do this? =

Got an idea for a new feature or don't like how something already is? Please speak up in the [support forum](https://wordpress.org/support/plugin/challonge)! A lot of the features in the plugin today have been suggested or influenced by the community. If there's something you'd like to see, chances are others may want to see it as well.

= How can I help you out? =

I do not have a lot of time to actually test everything I put into this plugin. It has already happened where I make something work and the next thing I know, it doesn't work because I forgot one small detail. So, testing this plugin out for me and letting me know what you find would be a big help! If you don't want to do that, you could always [donate](https://zavaboy.org/challonge-wordpress-plugin/). Donating will keep me active on this project.

== Screenshots ==

1. It's easy to embed a tournament bracket with the shortcode. The widgets make singing up to a tournament and reporting scores easy.
2. If no "url" attribute is set in your shortcode, a tournament listing will be displayed.
3. Signing up to a tournament is easy.
4. Reporting your score is also easy.
5. Control things like how player names are defined and how scoring works in the settings page.

== Changelog ==

= 1.1.6 =
* Tested in WordPress 4.9
* Fixed issue where widget Signup buttons on tournaments with one or more participants are not displayed for users not logged in. Thanks to sagund07 for finding the cause and giving a solution for this issue.
* Added comments in code about relevant functionality changes in WordPress 4.6.
* Changed some class names in settings to be less conflicting with other plugin CSS that may be loaded as well.
* Added a way to list participants to shortcodes.
* Fixed insecure content loading in iframes on secure pages.
* Improved caching for much faster page loads.
* Added adaptive caching where the expire date is determined by the cached content.
* Added cache age to bottom corner shortcodes and widgets which also allows manual refreshing.

= 1.1.5 =
* Moved widget content functions to a new class for Ajax related methods.
* Moved development features to separate class.
* Made an option to disable the exclusion setting in each tournament from Challonge.com.
* Added status-specific visibility options to shortcodes.
* Added option to manage shortcode tournament listings.
* Added user meta fields to participant name templating.
* Bumped tested WordPress version from 4.1 to 4.5.
* Fixed action buttons from replacing tournament link in listing.
* All buttons for the tournament now update after an action has taken place.
* Some general code cleanup.

= 1.1.4 =
* Added version information to settings page.
* Made plugin translation ready.
* Added version mismatch check to development notices.
* Added Spanish translation by Andrew Kurtis from WebHostingHub.com
* Fixed limits from displaying oldest instead of newest.
* Increased minimum WordPress version requirement from 3.2 to 3.3.
* Bumped tested WordPress version from 3.9 to 4.0.
* Fixed a issue when viewing a bracket from the widget on secondary multisite.
* Tournament information now displays the amount of time before a tournament is scheduled to start in addition to its created date.
* Added support for checking in during the check in period if the participant isn't already checked in.
* Participants may now forfeit during the check in period.
* Widget and shortcode displays an "unavailable" message instead of displaying "no tournaments" when the API request fails.
* Added "checking in" and "checked in" to widget status filter. Additionally added "Unknown/Other" to cover new or unknown statuses.
* Return an error if an AJAX request fails.
* Fixed dates from API. (I was doing it wrong.)
* Thickbox is sized to fit the screen when viewing a tournament bracket.
* Improved regular expressions for more flexibility for input values.
* Added flexibilty to Subdomain input in widget settings. (eg. now accepts "my-subdomain.challonge.com")
* Added a Challonge TinyMCE editor button. (Just copy/paste the URL of the listing or tournament you want to add to the page.)
* Made minor improvements in the code and some cleanup.

= 1.1.3 =
* Increased CSS z-index for thickbox.
* Added proper support for SSL verification.
* The API key verification on the settings page will now catch response failures and display an error.
* Added cURL detection to notify user if cURL is not installed.
* Added a way to hide the donation box in case it gets in the way.
* Changed default caching value to "0".
* Added method to handle version update changes. (I didn't need it until now.)
* Improved admin notice transient.
* Added a screenshot of the settings page.
* Increased minimum WordPress version requirement from 3.1 to 3.2.
* Made additional minor changes.

= 1.1.2 =
* Fixed a few misspellings in the settings page.
* Removed the minor security fix I released with last update. It caused a redirection loop with some plugins.
* Updated CSS to force thickbox on top of theme elements. This takes the place of the previous CSS fix for the twentyeleven theme.
* Tested in WordPress 3.9.

= 1.1.1 =
* Fixed API Caching from handling non-XML responses as XML and throwing a fatal error.
* Added a way around secondary copies of jQuery from overriding the Challonge plugin.
* Fixed score reporting when scoring is on second setting and opponent scoring is disabled and the participant is matched with a opponent that signed up via other means.
* Fixed a minor security issue.
* Fixed the Signup button actions for unregistered users.
* Added some CSS to prevent twentyeleven theme header from showing over thickboxes.
* Some general code cleanup.

= 1.1.0 =
* Added participant name templating with tokens.
* Added scoring settings.
* Widget and short code bracket names no longer are direct links to the brackets.
* Added tournament description inside signup window.
* Added administrative notices to aid my development.
* Added caching for API responses.
* Fixed tournament list sorting.
* Added "limit" attribute to short code.
* The signup button can now optionally be displayed publicly.
* If a tournament has "Quick Advance" enabled, only a win or loss can be reported.
* Added status filter to widgets.

= 1.0.7 =
* Fixed progress bars for Google Chrome v32.
* Corrected link to get a Challonge API key. (It was moved.)

= 1.0.6 =
* Fixed unregistered user permissions.

= 1.0.5 =
* Fixed subdomain tournament actions in widget.

= 1.0.4 =
* Fixed issue with widget tournament limit.
* Made changes to readme.

= 1.0.3 =
* The API Key setting will display unexpected errors instead of nothing.
* Added a way to disable SSL verification in settings.
* Signing up for a tournament with a participant with the same username will now give you an alternate username.
* Tied games are reported correctly now.

= 1.0.2 =
* Updates for WordPress.org. There were no changes to the code.

= 1.0.1 =
* API key validation fix
* Additional API key related fixes

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.6 =
Tested in WordPress 4.9
Fixed issue where widget Signup buttons on tournaments with one or more participants are not displayed for users not logged in. Thanks to sagund07 for finding the cause and giving a solution for this issue.
Added comments in code about relevant functionality changes in WordPress 4.6.
Changed some class names in settings to be less conflicting with other plugin CSS that may be loaded as well.
Added a way to list participants to shortcodes.
Fixed insecure content loading in iframes on secure pages.
Improved caching for much faster page loads.
Added adaptive caching where the expire date is determined by the cached content.
Added cache age to bottom corner shortcodes and widgets which also allows manual refreshing.

= 1.1.5 =
Moved widget content functions to a new class for Ajax related methods.
Moved development features to separate class.
Made an option to disable the exclusion setting in each tournament from Challonge.com.
Added status-specific visibility options to shortcodes.
Added option to manage shortcode tournament listings.
Added user meta fields to participant name templating.
Bumped tested WordPress version from 4.1 to 4.5.
Fixed action buttons from replacing tournament link in listing.
All buttons for the tournament now update after an action has taken place.
Some general code cleanup.

= 1.1.4 =
Added version information to settings page.
Made plugin translation ready.
Added version mismatch check to development notices.
Added Spanish translation by Andrew Kurtis from WebHostingHub.com
Fixed limits from displaying oldest instead of newest.
Increased minimum WordPress version requirement from 3.2 to 3.3.
Bumped tested WordPress version from 3.9 to 4.0.
Fixed a issue when viewing a bracket from the widget on secondary multisite.
Tournament information now displays the amount of time before a tournament is scheduled to start in addition to its created date.
Added support for checking in during the check in period if the participant isn't already checked in.
Participants may now forfeit during the check in period.
Widget and shortcode displays an "unavailable" message instead of displaying "no tournaments" when the API request fails.
Added "checking in" and "checked in" to widget status filter. Additionally added "Unknown/Other" to cover new or unknown statuses.
Return an error if an AJAX request fails.
Fixed dates from API. (I was doing it wrong.)
Thickbox is sized to fit the screen when viewing a tournament bracket.
Improved regular expressions for more flexibility for input values.
Added flexibilty to Subdomain input in widget settings. (eg. now accepts "my-subdomain.challonge.com")
Added a Challonge TinyMCE editor button. (Just copy/paste the URL of the listing or tournament you want to add to the page.)
Made minor improvements in the code and some cleanup.

= 1.1.3 =
Increased CSS z-index for thickbox.
Added proper support for SSL verification.
The API key verification on the settings page will now catch response failures and display an error.
Added cURL detection to notify user if cURL is not installed.
Added a way to hide the donation box in case it gets in the way.
Changed default caching value to "0".
Added method to handle version update changes. (I didn't need it until now.)
Improved admin notice transient.
Added a screenshot of the settings page.
Increased minimum WordPress version requirement from 3.1 to 3.2.
Made additional minor changes.

= 1.1.2 =
Fixed a few misspellings in the settings page.
Removed the minor security fix I released with last update. It caused a redirection loop with some plugins.
Updated CSS to force thickbox on top of theme elements. This takes the place of the previous CSS fix for the twentyeleven theme.
Tested in WordPress 3.9.

= 1.1.1 =
Fixed API Caching from handling non-XML responses as XML and throwing a fatal error.
Added a way around secondary copies of jQuery from overriding the Challonge plugin.
Fixed score reporting when scoring is on second setting and opponent scoring is disabled and the participant is matched with a opponent that signed up via other means.
Fixed a minor security issue.
Fixed the Signup button actions for unregistered users.
Added some CSS to prevent twentyeleven theme header from showing over thickboxes.
Some general code cleanup.

= 1.1.0 =
Added participant name templating with tokens.
Added scoring settings.
Widget and short code bracket names no longer are direct links to the brackets.
Added tournament description inside signup window.
Added administrative notices to aid my development.
Added caching for API responses.
Fixed tournament list sorting.
Added "limit" attribute to short code.
The signup button can now optionally be displayed publicly.
If a tournament has "Quick Advance" enabled, only a win or loss can be reported.
Added status filter to widgets.

= 1.0.7 =
Fixed progress bars for Google Chrome v32.
Corrected link to get a Challonge API key. (It was moved.)

= 1.0.6 =
Fixed unregistered user permissions.

= 1.0.5 =
Fixed subdomain tournament actions in widget.

= 1.0.4 =
Fixed issue with widget tournament limit.

= 1.0.3 =
The API Key setting will display unexpected errors instead of nothing.
Added a way to disable SSL verification in settings.
Signing up for a tournament with a participant with the same username will now give you an alternate username.
Tied games are reported correctly now.

= 1.0.2 =
Updated a few things for WordPress.org. No huge benefit upgrading from 1.0.1.

= 1.0.1 =
Fixed API key validation to work with new (mixed-case) API keys.

= 1.0 =
Initial release.
