=== Login Security ===
Contributors: funnycat
Donate link: http://www.infowebmaster.fr/dons.php
Tags: attack, authentication, ban, banned, blacklist, block, brute, brute force, failed login, force, hack, lock, login, security, spam
Requires at least: 3.0
Tested up to: 4.1.0
Stable tag: 1.0.1
License: GPLv2 or later



Improves the security of the login page against brute-force attacks. Records every attempts to login. Easily block an IP address.

== Description ==

A lot of brute-force attacks are performed on WordPress websites without you probably noticed it. These attacks are performed by computer programs and consists to try every possible password until to find the correct one. If you use a popular password such as "123456" or "qwerty" it's very easy to access your website through a brute force attack.

Login Security is a plugin that tries to protect you against such attacks. Every successful or failed login attempts are recorded. You will probably discover that a lot of brute force attacks are performed on your website. This plugin can tell you how many times an IP address tried to access to the Back-Office of your WordPress website. Then you can easily block the access of this IP address in just "one click".

On the tested websites there was an average of over 800 login attempts per day.


= Current features =

*   Records every login attempts (failed or successful). Can be used to tell you when a user login
*   Display all the failed login or successful login with the associated IP, User-Agent and HTTP referer
*   Stats over the number of failed login during last 7 days and last 12 months
*   Discover which IP address tries the most to access your website
*   Banned an IP address
*   Multi languages : English, French (from : France, Canada, Belgium, Switzerland and Luxembourg)


Want a WordPress developper? Want to add a translation? Feel free to <a href="http://en.tonyarchambeau.com/contact.html">contact me</a>.


== Installation ==
1. Unzip the plugin and upload the "login-security" folder to your "/wp-content/plugins/" directory
2. Activate the plugin through the "Plugins" administration page in WordPress
3. That's it, the plugin is enabled and will start to log every successful or failed login.


== Frequently Asked Questions ==
= Will I be protect a 100% against brute-force attack? =
No, this plugin only offer a way to record every brute-force attack and block the IP address used during these attacks. You still have to follow some rules, such as to be sure your login and password are difficult to find.


== Screenshots ==
1. Graphical user interface that display the number of login attempts during the previous 7 days


== Changelog ==

= 1.0.2 =
Debug the graphic of the last 12 months.
= 1.0.1 =
Initial Release.


== Upgrade notice ==


== How to uninstall Login Security ==
To uninstall this plugin, you just have to de-activate the plugin from the plugins list.
