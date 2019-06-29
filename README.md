# EsvMediaWikiExtension

This [MediaWiki](https://www.mediawiki.org) extension allows a Bible verse (from the [English Standard Version (ESV)](https://www.esv.org) of the Bible) to be included in a MediaWiki.

## Installation

* [Download](https://github.com/kungfubonanza/EsvMediaWikiExtension/archive/master.zip) the extension, unzip it, and place in a directory called `Esv` in your `extensions` folder.
* In Esv.hooks.php, replace the phrase `"INSERT KEY HERE"` with your [ESV API key](https://api.esv.org/docs/).
* Add the following code to the bottom of your [LocalSettings.php](https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:LocalSettings.php): `wfLoadExtension( 'Esv' );`


## Usage

| Wikitext            | Rendering |
| --------------------| --------- |
| `<esv>Matt 1:1</esv>` | **Matt 1:1**: The book of the genealogy of Jesus Christ, the son of David, the son of Abraham.  |
| `<esv format="html">Matt 1:1</esv>` | **Matt 1:1**: The book of the genealogy of Jesus Christ, the son of David, the son of Abraham.  |
| `<esv format="text">Matt 1:1</esv>` | **Matt 1:1**: The book of the genealogy of Jesus Christ, the son of David, the son of Abraham.  |
| `<esv>Matt 1:1\|Jesus Christ</esv>` | **Matt 1:1**: The book of the genealogy of **Jesus Christ**, the son of David, the son of Abraham.  |
| `<esvlist>* Matt 1:1\|Jesus Christ</esvlist>` | &bull; **Matt 1:1**: The book of the genealogy of **Jesus Christ**, the son of David, the son of Abraham.
