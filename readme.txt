=== Challonge ===
Contributors: zavaboy
Donate link: http://zavaboy.org/2013/02/09/challonge-wordpress-plugin/
Tags: plugin, widget, shortcode, api, integration, embed, events, games, tournaments, matches, Challonge
Requires at least: 3.1
Tested up to: 3.7
Stable tag: trunk
License: MIT License
License URI: http://opensource.org/licenses/MIT

Integrates Challonge, a handy bracket generator, in WordPress.

== Description ==

Do you use [Challonge](http://challonge.com/ "a handy bracket generator") for your gaming or sport events? The Challonge plugin integrates your Challonge tournaments into your WordPress website so your users may easily see recent tournaments, their progress, and even sign up as a participant. You may give your users the ability to report their own scores.

= Features =

* Only registered users may sign up to your tournaments.
* Likewise, your users may forfeit a tournament after signing up, but only before it begins.
* Participating users may report their own scores.
* Includes role capabilities. You will need a role managment plugin to change who has these capabilities.
* ***Coming Soon:** Caching to speed up page load time.*

= Getting Started =

Before you start using this plugin, here's what you'll need:

* A [Challonge.com](http://challonge.com/) account. Signup is free.
* A valid 'Developer API Key' so the Challonge plugin can talk with your Challonge.com account.

Once you have the Challonge plugin installed and activated on your website, you will need to enter your Challonge.com API key in 'Settings' > 'Challonge'. Once you have done that, you have unlocked the full power of this nice plugin.

= Using This Plugin =

This is the kind of stuff you can now do:

You may use a shortcode in posts and pages to display a tournament or list out tournaments.

* **`[challonge url="w4la9fs6"]`** - This will embed a tournament bracket. This may be any Challonge bracket, not just your own.
* **`[challonge subdomain="my_sub"]`** - This will list out all tournament brackets in the 'my_sub' Challonge.com subdomain, or organization.
* **`[challonge url="w4la9fs6" theme="2" show_final_results="1" width="90%" height="600px"]`** - This is just a more customized version of the first shortcode.

The shortcode has the following attributes:

**Challonge Module Options:** ( See: http://challonge.com/module/instructions )

* **`url`** - The URL to a tournament.
* **`subdomain`** - The subdomain of the tournament URL or if no tournament URL is provided, the listing will be tournaments within the specified subdomain.
* **`theme`** - The theme ID you would like to use.
* **`multiplier`** - Scales the entire bracket.
* **`match_width_multiplier`** - Scales the width allotted for names.
* **`show_final_results`** - Display the final results above your bracket.
* **`show_standings`** - For round robin and Swiss tournaments, you can opt to show a table of the standings below your bracket.

**Challonge Plugin Options:**

* **`width`** - The width of the embedded tournament bracket.
* **`height`** - The height of the embedded tournament bracket.
* **`allowusers`** / **`denyusers`** / **`allowroles`** / **`denyroles`** - A comma separated list of users or roles you would like to specifically allow or deny from viewing the tournament bracket.

To allow your users to signup and report their own scores, just add the plugin widget.

The widget has the following options:

* **Title** - The title of the widget, nothing special here. Defaults to 'Challonge'.
* **Subdomain** - The subdomain to list your tournaments from. (Optional)
* **Tournament Filter** - Only tournament names that match this filter will be listed. (Optional) This may be a simple wildcard filter, for example `My * Tournament` will match 'My Big Tournament' but not 'Your Big Tournament'. If you need a more robust filter, you may use Regular Expressions (PCRE) like so: `/My \d+(st|nd|rd|th) Tournament/i` will match 'My 3rd tournament' but not 'My Third Tournament'
* **Max tournaments listed** - The maximum number of tournaments that the widget will list. Defaults to 10.

= Integrating Challonge.com Tournaments =

Challonge.com tournaments may be easily setup to allow your WordPress users to signup and report scores. Here are a few things you should know when setting up your Challonge.com tournaments:

* Turning 'Host a sign-up page' on will allow your users to signup through the widget.
* In 'Advanced Options' > 'Permissions': Turning 'Allow participants with Challonge accounts to report their own scores.' on will allow your users to report their own scores through the widget.
* In 'Advanced Options' > 'Permissions': Turning 'Exclude this event from search engines and the public browsable index.' on will hide the tournament from the shortcode and widget tournament listings.

= Did You Know? =

If you run the same tournaments on multiple WordPress websites, your WordPress users will be tracked in your Challonge.com tournaments by their email address and login name, even if their display name differs. With this in mind, users who change their email address will lose access to any of their preexisting tournament signups.

Good luck!

== Installation ==

1. Upload the `challonge` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. You should add your Challonge.com 'Developer API Key' to 'Settings' > 'Challonge'.

== Frequently Asked Questions ==

= Why was this Challonge plugin made? =

At first, I needed a WordPress plugin to help with some tournaments I was involved with and nobody had made one or was working on one at that time, so I started making this plugin. Later, I no longer needed a plugin, but I already started the project and people have already noticed. After about a year of life distractions and general procrastination, I completed the first version.

= Do I need a Challonge.com account to use this plugin? =

Actually, no you don't. (Keep reading!) Without an account, you will only be able to embed tournament brackets into posts and pages with the shortcode, but you will not be able to get any tournament listings with the shortcode or in the widget. You will need a valid Challonge API key, which you can get easily with a [Challonge.com](http://challonge.com/) account, to use all the Challonge WordPress plugin has to offer. A Challonge.com account is free.

= How can I help you out? =

I do not have a lot of time to actually test everything I put into this plugin. It has already happened where I make something work and the next thing I know, it doesn't work because I forgot one small detail. So, testing this plugin out for me and letting me know what you find would be a big help! If you don't want to do that, you could always donate. Donating will keep me active on this project.

== Screenshots ==

1. A shortcode tournament bracket and a few widgets on the right.
2. How you report your score.
3. Tournament listing from shortcode.

== Changelog ==

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
