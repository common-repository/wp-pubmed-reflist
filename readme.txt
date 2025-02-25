=== WP Pubmed Reflist ===
Contributors: Jim Hu
Tags: shortcodes, pubmed, references
Requires at least: 3.0
Tested up to: 5.2.2
Donate link:http://biochemistry.tamu.edu/index.php/alum/giving/
Stable tag: 1.41
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to use a pubmed query to generate a reference list on a page

== Description ==

This plugin uses a pubmed query to generate a reference list on a page. It provides an admin page
where you can associate PubMed queries with keys and a shorttag that allows you to display a 
list of references based on any of your keys on a page or post.

This plugin was developed for use on the website for the Department of Biochemistry and Biophysics
at Texas A&M University (http://biochemistry.tamu.edu).

== Installation ==

1. Upload `wp-pubmed-reflist` folder to your `/wp-content/plugins` folder
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use Settings to associate keys with PubMed queries.
4. Use the shortcode in your posts or pages: [pmid-refs key="<key>" limit=10]

== Installation ==
requires php openSSL to access pubmed E-utils via https
== Frequently Asked Questions ==
= How do I compose a query =
For Query syntax
See: http://www.ncbi.nlm.nih.gov/books/NBK3830/
= My reference list just shows a list of numbers and no text =
This is probably because an error happened in processing the query. The empty 
result gets cached. To flush the cache go back and resave the queries in the dashboard; then reload the page. 
= Can I combine saved queries =
Yes. use double pipes to combine other queries using their named keys. 
The plugin will combine the two queries with a logical OR
= Can I display a random reference based on my query =
Yes. Use negative limit to pick one random reference from a list of abs[$limit]
= Can I add references that are not in PubMed =
Yes, add these to the extras box. Use one reference per line. These will be added to the
end of your list. The plugin is not smart enough to sort these into the main list at this
time.
= Your donate link seems odd. How do I support this plugin =
This plugin was developed as part of my work for the Department of Biochemistry and Biophysics
at Texas A&M. Donating won't lead me to give up my day job to spend more time on plugin
development. But if you want to donate out of gratitude/niceness, give whatever you think 
would be appropriate to the Biochemistry/Biophysics improvement fund. It's tax-deductible
and it will go to some other worthy activity.
= Are these questions really frequently asked? =
No. I'm just guessing what people will ask.
== Screenshots ==
1. Admin page
2. Page displaying a reference list using the shorttag

== Upgrade Notice ==
n/a
== Changelog ==
TODO:
* input validation for the admin page
* jquery datatables for settings view
= 1.41 =
* Found regex error in query_form. Fixed, I hope.
= 1.3 =
* Change handling of disallowed characters in query keys in hopes of dealing with a mysterious problem where on some installations keys are showing illegal chars when the chars are all legal.
* Add support for NCBI API keys (optional)

= 1.2 =
* Preprocess ArticleTitle and AbstractText wrap them in CDATA sections. This preserves HTML inside the Titles and Abstracts, such as superscripts.
= 1.1 =
* changed to use https to access pubmed E-utils. Now requires that php have openssl installed.
* fix some comments that didn't seem to make sense
* removed some debugging lines
* quoted URL in pubmed link display
= 1.0 =
* Add ability to select more than one reference at random
* Add strong emphasis for specific authors
* Add ability to use curl vs remote file opening, and to throw an error if neither is available.
* minor bug fixes
= 0.9 =
* Fix bug to allow more than 20 references to be retrieved
= 0.8 =
* Fixed markdown in readme
* add _TitleL formatting tag for Title with link
* Fix case sensitive filename bug in 0.7
= 0.7 =
* Moved admin menu to go under tools and modified user permissions to allow Editors to edit queries
* Moved new key from the bottom to the top of the query management form
* Tabbed admin page for additional settings
* Users can now customize output formats
..* set where to truncate author lists and at et al.
..* set key phrases to italicize (e.g. in vitro or species names)
* Help tab documents how to use the plugin
= 0.6 =
* Changed recursive query syntax to use *key*
= 0.5 =
* Add fulltext and PMC links
* Allow customization of text link to pubmed
* change display if there are zero hits
= 0.4 =
* integrate non-PMID citations
= 0.3 =
added the ability to delete a key - query pair from the admin settings UI
added the ability to construct complex queries using other keys.
	e.g.
		smith => smith j[au] AND escherichia coli[majr]
		jones => jones jp[au] AND enzyme
		smith_jones = smith || jones 
			= (smith j[au] AND escherichia coli[majr])OR(jones jp[au] AND enzyme)
= 0.2 =
* convert to OO
* condense options to a single array
= 0.1 =
* The prototype
