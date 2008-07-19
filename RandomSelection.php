<?php
/*
 
 RandomSelection v2.1.2 -- 5/8/08
 
 This extension randomly displays one of the given options.
 
 Usage: <choose><option>A</option><option>B</option></choose>
 Optional parameter: <option weight="3"> == 3x weight given
 
 Author: Ross McClure [http://www.mediawiki.org/wiki/User:Algorithm]
*/
 
$wgExtensionFunctions[] = "wfRandomSelection";
$wgExtensionCredits['parserhook'][] = array(
'name' => 'RandomSelection',
'url' => 'http://www.mediawiki.org/wiki/Extension:RandomSelection',
'version' => '2.1.2',
'author' => 'Ross McClure',
'description' => 'Displays a random option from the given set.'
);
 
function wfRandomSelection() {
    global $wgParser;
    $wgParser->setHook( "choose", "renderChosen" );
}
 
function renderChosen( $input, $argv, &$parser ) {
    # Prevent caching
    $parser->disableCache();

		$pick = 1;
		if (isset($argv['pick'])) $pick = intval($argv['pick']);
 
    # Parse the options and calculate total weight
    $len = preg_match_all("/<option(?:(?:\\s[^>]*?)?\\sweight=[\"']?([^\\s>]+))?"
        . "(?:\\s[^>]*)?>([\\s\\S]*?)<\\/option>/", $input, $out);
    $tw = 0;
    for($i=0; $i<$len; $i++) {
        if(strlen($out[1][$i])==0) $out[1][$i] = 1;
        else $out[1][$i] = intval($out[1][$i]);
        $tw += $out[1][$i];
    }

	$input = "";
	for($j=0; $j<$pick; $j++) {
		# Choose an option at random
		if($tw <= 0) return "";
		$r = mt_rand(1,$tw);
		for($i=0; $i<$len; $i++) {
			$r -= $out[1][$i];
			if($r <= 0) {
				$input .= $out[2][$i];
				$tw -= $out[1][$i];
				$out[1][$i] = 0; # Prevents this item being picked twice
				break;
			}
		}
	}
 
    # If running new parser, take the easy way out
    if( defined( 'Parser::VERSION' ) && version_compare( Parser::VERSION, '1.6.1', '>' ) ) {
        return $parser->recursiveTagParse($input);
    }
 
    # Otherwise, create new parser to handle rendering
    $localParser = new Parser();
 
    # Initialize defaults, then copy info from parent parser
    $localParser->clearState();
    $localParser->mTagHooks         = $parser->mTagHooks;
    $localParser->mTemplates        = $parser->mTemplates;
    $localParser->mTemplatePath     = $parser->mTemplatePath;
    $localParser->mFunctionHooks    = $parser->mFunctionHooks;
    $localParser->mFunctionSynonyms = $parser->mFunctionSynonyms;
 
    # Render the chosen option
    $output = $localParser->parse($input, $parser->mTitle,
                                  $parser->mOptions, false, false);
    return $output->getText();
}

