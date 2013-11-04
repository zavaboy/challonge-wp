=== Challonge ===
Contributors: zavaboy
Donate link: http://zavaboy.org/
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

= Getting Started =

A couple terms we will use here:
* 'Challonge' refers to the Challonge.com website.
* 'Plugin' refers to this Challonge WordPress plugin.

To get started, first you must make sure you have provided a valid Challonge API key to the plugin.

A valid API key will look something like this:
`t6m32vtq94io2h2jgbwtu5078vmm4uny0a136vuf`

Once you get that in, you are ready to start using the plugin.

You may use a short code in posts and pages to display a tournament or list out tournaments.
* `[challonge url="w4la9fs6"]`
* `[challonge subdomain="my_sub"]`
* `[challonge url="w4la9fs6" theme="2" show_final_results="1" width="90%" height="600px"]`

The short code has the following attributes:
* url - The URL to a tournament.
* subdomain - The subdomain of the tournament URL or if no tournament URL is provided, the listing will be tournaments within the specified subdomain.
* theme - The theme ID you would like to use. See: http://challonge.com/themes/1/edit
* multiplier - Scales the entire bracket. See: http://challonge.com/module/instructions
* match_width_multiplier - Scales the width allotted for names. See: http://challonge.com/module/instructions
* show_final_results - Display the final results above your bracket. See: http://challonge.com/module/instructions
* show_standings - For round robin and Swiss tournaments, you can opt to show a table of the standings below your bracket. See: http://challonge.com/module/instructions
* width - The width of the embedded tournament bracket.
* height - The height of the embedded tournament bracket.
* allowusers/denyusers/allowroles/denyroles - A comma separated list of users or roles you would like to specifically allow or deny from viewing the tournament bracket.

To allow your users to signup and report their own scores, just add the plugin widget.

The widget has the following options:
* Title - The title of the widget, nothing special here.
* Subdomain - The subdomain to list your tournaments from. (Optional)
* Tournament Filter - Filter the names of the tournaments. This may be a simple wildcard filter, for example `My * Tournament` will match 'My Big Tournament' but not 'Your Big Tournament'. If you need a more robust filter, you may use Perl Compatible Regular Expressions (PCRE) like so: `/My \d+(st|nd|rd|th) Tournament/i` will match 'My 3rd tournament' but not 'My Third Tournament'
* Max Tournaments Listed - The maximum tournaments that the widget will list.

Here are some things you should know when setting up your tournament on Challonge:
* Turning 'Host a sign-up page' on will allow your users to signup through the widget.
* In 'Advanced Options' > 'Permissions', turning 'Allow participants with Challonge accounts to report their own scores.' on will allow your users to report their own scores through the widget.
* In 'Advanced Options' > 'Permissions', turning 'Exclude this event from search engines and the public browsable index.' on will hide the tournament from the short code and widget tournament listings.

Good luck!

== Installation ==

1. Upload the `challonge` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. You will have to add your Challonge API key in 'Settings' > 'Challonge'.

== Frequently Asked Questions ==

= Why was this Challonge plugin made? =

At first, I needed a WordPress plugin to help with some tournaments I was involved with and nobody had made one or was working on one at that time, so I started making this plugin. Later, I no longer needed a plugin, but I already started the project and people have already noticed. After about a year of life distractions and general procrastination, I completed the first version.

== Screenshots ==

1. A short code tournament bracket and a few widgets on the right.
2. How you report your score.
3. Tournament listing from short code.

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
Initial release.
