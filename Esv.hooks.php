<?php
/**
 * Hooks for Esv extension
 *
 * @file
 * @ingroup Extensions
 */

class EsvHooks {

   /**
    * Register parser hooks
    * See also http://www.mediawiki.org/wiki/Manual:Parser_functions
    */
   public static function onParserFirstCallInit( &$parser ) {
       // Add the following to a wiki page to see how it works:
       //  <dump>test</dump>
       //  <dump foo="bar" baz="quux">test content</dump>
       $parser->setHook( 'esv', 'EsvHooks::parserTagEsv' );
       $parser->setHook( 'esvlist', 'EsvHooks::parserTagEsvList' );
       $parser->setHook( 'openlibrary', 'EsvHooks::parserTagOpenLibrary' );
       return true;
   }

   public static function getEsvText( $verse, $format ) {
      # grab the scripture from the ESV server
      global $wgEsvApiKey;
      $passage = urlencode(trim(preg_replace('/\s\s+/', ' ', $verse)));

      $options = "include-passage-references=false&include-first-verse-numbers=false&include-chapter-numbers=false&include-verse-numbers=false&include-footnotes=false&include-short-copyright=false&include-passage-horizontal-lines=false&include-heading-horizontal-lines=false&include-headings=false&include-subheadings=false&include-audio-link=false&include-short-copyright=false&indent-poetry=false&indent-poetry-lines=0"; // plaintext

      $url = "https://api.esv.org/v3/passage/$format/?q=$passage&$options";
      $ch = curl_init($url); 
      $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => array(
                "Accept: application/json",
                "Authorization: Token $wgEsvApiKey",
                )
      );
      
      curl_setopt_array($ch, $options); 
      $response = curl_exec($ch);
      curl_close($ch);
      
      $obj = json_decode($response, true);

      $verseText = $obj["passages"][0];

      if("html" == $format)
      {
         return $verseText;
      }
      else
      {
         // remove unwanted extra spaces and line returns
         return trim(preg_replace('/\s\s+/', ' ', preg_replace('/[\r\n]+/', ' ', $verseText)));
      }
   }

   /**
    * Parser hook handler for <esv>
    *
    * @param string $data: The content of the tag.
    * @param array $params: The attributes of the tag.
    * @param Parser $parser: Parser instance available to render
    *  wikitext into html, or parser methods.
    * @param PPFrame $frame: Can be used to see what template
    *  arguments ({{{1}}}) this hook was used with.
    *
    * @return string: HTML to insert in the page.
    */
   public static function parserTagEsv( $data, $argv, $parser, $frame ) {
       $esv =  array(
           'content' => $data,
           'attributes' => (object)$argv,
       );

       $format = 'text';
       if(isset($argv['format']))
       {
           $format = $argv['format'];
       }

       # prepare the input:
       # 1. recursively preprocess to ensure arguments to templates are expanded
       #    before looking them up (i.e., look up "Jn 3:16", not "{{{1}}}"
       # 2. remove unnecessary whitespace
       # 3. trim whitespace from the beginning and end of the string
       $input = trim(preg_replace('/\s\s+/', ' ', $parser->recursiveTagParse(htmlspecialchars($data), $frame)));

       return EsvHooks::getTextAndHighlight($input, $format);

       return $html;
   }

   public static function getTextAndHighlight( $input, $format ) {
       # split the string into two parts
       $input = str_replace(" | ", '|', $input);
       list($verse, $highlightText) = explode('|', $input, 2);

       # grab the verse
       $verseText = EsvHooks::getEsvText($verse, $format);

       # highlight the text if the user provided text to highlight
       if(NULL != $highlightText)
       {
          $verseText = str_replace($highlightText, '<b>' . $highlightText . '</b>', $verseText);
       }

       # grab the scripture from the ESV server
       // Very important to escape user data with htmlspecialchars() to prevent
       // an XSS security vulnerability.
       return '<b>' . htmlspecialchars($verse) . '</b>: ' . $verseText;
   }

   /**
    * Parser hook handler for <esvlist>
    *
    * @param string $data: The content of the tag.
    * @param array $params: The attributes of the tag.
    * @param Parser $parser: Parser instance available to render
    *  wikitext into html, or parser methods.
    * @param PPFrame $frame: Can be used to see what template
    *  arguments ({{{1}}}) this hook was used with.
    *
    * @return string: HTML to insert in the page.
    */
   public static function parserTagEsvList( $data, $attribs, $parser, $frame ) {
       //# preprocess to make template-friendly
       //$string = $parser->recursiveTagParse($data, $frame);

       # grab each item in the list (each item should look something
       # like "* Jn 3:16" or "* Jn 3:16|loved)
       $pattern = "/\* (.*)/";
       $result = preg_replace_callback($pattern,
                 function($verses) {
                    # reconstruct the line, replacing the verse with the
                    # the verse in bold and the expanded text
                    # - remember the whitespace at the beginning
                    # - trim any superfluous line returns to prevent the each
                    #   item in the list from being treated as a standalone
                    #   single-<li> list
                    return trim('* ' . EsvHooks::getTextAndHighlight($verses[1], "html"));
                 },
                 $data);

       return $result;
   }

   /**
    * Parser hook handler for <openlibrary>
    *
    * @param string $data: The content of the tag.
    * @param array $params: The attributes of the tag.
    * @param Parser $parser: Parser instance available to render
    *  wikitext into html, or parser methods.
    * @param PPFrame $frame: Can be used to see what template
    *  arguments ({{{1}}}) this hook was used with.
    *
    * @return string: HTML to insert in the page.
    */
   public static function parserTagOpenLibrary( $data, $attribs, $parser, $frame ) {
      $ISBN = urlencode($data);
      $options = "bibkeys=ISBN:$ISBN&format=json&jscmd=data";
      $url = "https://openlibrary.org/api/books?$options";
      $ch = curl_init($url); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      $response = curl_exec($ch);
      curl_close($ch);

      $obj = json_decode($response, true);
      $subtitle = $obj["ISBN:$ISBN"]["subtitle"];
      $cover = $obj["ISBN:$ISBN"]["cover"]["medium"];
      $name = $obj["ISBN:$ISBN"]["title"];
      if(NULL == $subtitle)
      {
            $title_orig = $name;
      }
      else
      {
            $title_orig = "$name: $subtitle";
      }

      $author = $obj["ISBN:$ISBN"]["authors"][0]["name"];
      $publisher = $obj["ISBN:$ISBN"]["publishers"][0]["name"];
      $release_date = $obj["ISBN:$ISBN"]["publish_date"];
      $goodreads = $obj["ISBN:$ISBN"]["identifiers"]["goodreads"][0];

      $text = $parser->recursiveTagParse("{{InfoboxBook|cover = $cover|name = $name|title_orig = $title_orig|author = $author|publisher = $publisher|release_date = $release_date|isbn = ISBN $ISBN|goodreads = $goodreads}}", $frame);

       return $text;
   }
}
