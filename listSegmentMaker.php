<?php
ini_set('include_path','..'.DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.PATH_SEPARATOR.ini_get('include_path'));
/*
 * listSegmenterMaker
 *
 * This script looks at your last mailchimp campaigns. if the campaign name (not
 * subject line) has the query in it then it will gather together all of the
 * addresses of people who have opened that campaign. It will do this for every
 * campaign that matches the criteria. It will then either create a new segment
 * or update an existing segment.
 */

/*
 * The MailChimp API wrapper
 * http://apidocs.mailchimp.com/api/downloads/
 */
require_once 'MCAPI.php';


/*
 * Initalize needed variables
 */
$campaignIds       = array();
$finalListofEmails = array();
$segmentId         = null;
$segmentTitle      = null;


/*
 * Fetch and validate the cli options
 * 
 * CLI Options
 * -l = listId. Required
 * -s = segmentId. If it does not exist then create it 
 * -n = Segment name. Require for new segments, optional for existing segments.
 * -q = The query to look for in the campaign name.
 * -f = fetch list of segments and IDs for a given list
 * -g = fetch list of lists
 * -v = verbose mode. shows what is going on.
 * -t = test mode. doesn't actually do anything.
 * 
 */
$options          = getopt("a:l:q:s::n::fvtg");
$apiKey           = isset($options['a'])?$options['a']:null;
$listId           = isset($options['l'])?$options['l']:null;
$segmentId        = isset($options['s'])?$options['s']:null;
$segmentTitle     = isset($options['n'])?$options['n']:null;
$fetchListMode    = isset($options['g'])?true:false;
$fetchSegmentMode = isset($options['f'])?true:false;
$verboseMode      = isset($options['v'])?true:false;
$testMode         = isset($options['t'])?true:false;

if ($verboseMode) {
    outputHeader();
}

if (is_null($apiKey)) {
    outputError("No mailchimp api key provided.\n",$verboseMode,$argv,$options);
    die(-1);    
}

if (!$fetchListMode and is_null($listId)) {
    outputError("No list id provided.\n",$verboseMode,$argv,$options);
    die(-2);
}


/*
 * If we are not asking for a list of lists or a list of segments, and a
 * segment id or title was not provided, bail.
 */
if ((!$fetchListMode and !$fetchListMode) and
    (is_null($segmentId) && is_null($segmentTitle))) {
    outputError("You must provide either a segment id or the title for a new segment.\n",$verboseMode,$argv,$options);
    die(-3);
}

/*
 * If verbose mode, motify the user of the options set.
 */
if ($verboseMode) {
    echo "Options\n";
    echo "=======\n";
    echo "MailChimp API Key  : {$apiKey}\n";
    echo "List Id            : {$listId}\n";
    echo "Segment Id         : {$segmentId}\n";
    echo "Segment Title      : " . (is_null($segmentId)?$segmentTitle:"IGNORED") . "\n";
    echo "Create New Segment : " . (is_null($segmentId)?"true":"false") . "\n";
    echo "Fetch list mode    : " . ($fetchListMode?"true":"false") . "\n";
    echo "Fetch segment mode : " . ($fetchSegmentMode?"true":"false") . "\n";
    echo "Test Mode          : " . ($testMode?"true":"false") . "\n\n";    
}

/*
 * Connect to MailChimp
 */
$api = new MCAPI($apiKey);

/*
 * If the user has asked for a list of list then just show that 
 * and bail. Fetch Mode assumes verbose mode.
 */
if ($fetchListMode) {
    $lists = $api->lists();
    echo "List of Lists\n";
    echo "=============\n";
    if (is_array($lists)) {
        foreach($lists['data'] as $list) {
            echo $list['id'] . " : " . $list['name'] . "\n";
        } // foreach($lists as $list)
    } else {
        echo "No List for this account.\n";
    }
    echo "\n\nDone\n";
    die(1);
}


/*
 * If the user has asked for a list of segments for a list then just show that 
 * and bail. Fetch Mode assumes verbose mode.
 */
if ($fetchSegmentMode) {
    $segments = $api->listStaticSegments($listId);
    echo "\nList Segments for List ID: ".$listId."\n";
    if (is_array($segments)) {
        foreach($segments as $segment) {
            echo $segment['id'] . " : " . $segment['name'] . "\n";
        } // foreach($segments as $segment)        
    } else {
        echo "No segments for this list.\n";
    }
    echo "\n\nDone\n";
    die(1);
} // if (isset($options['v']))


/*
 * Get a list of all available campaigns
 */
if ($verboseMode) {
    echo "Fetching list of campaigns.\n";
}
$listofcampaigns = $api->campaigns();

/*
 * If there is an error, deal with it.
 */
if ($api->errorCode) {
        $message = "Unable to fetch a list of campaigns!";
        $message .= "\n\tCode=".$api->errorCode;
        $message .= "\n\tMsg=".$api->errorMessage."\n";
        outputError($message,$verbose, $argv, $options);
    die(-2);
} // if ($api->errorCode)

/*
 * Get list of campaigns that match the query
 */
if ($verboseMode) {
    echo "Filtering campaigns.\n";
}
foreach($listofcampaigns['data'] as $campaign) {
    
    if (($campaign['list_id'] == $options['l']) &&
         stripos($campaign['title'],$options['q'])!==false ) {
        $campaignIds[] = $campaign['id'];
    }
} // foreach($listofcampaigns['data'] as $campaign)

/*
 * Now get every email address that opened each of the matching campaigns.
 */
if ($verboseMode) {
    echo "Fetching opens for each matching campaign.\n";
}
foreach($campaignIds as $thisCampaign) {
    $pageNo             = 0;
    $continueProcessing = true;    

    do
    {
        /*
         * We fetch in 1000 page chunks. No particular reason other than 100
         * seemed too samll.
         */
        $addressesThatOpenedThisCampaign = $api->campaignOpenedAIM($thisCampaign, $pageNo,1000);
        
        if ($api->errorCode==0) {
            /*
             * If for some reason we have no error but an empty array for the
             * data then bail.
             */ 
            if (count($addressesThatOpenedThisCampaign['data'])==0) {
                break;
            }
            
            foreach($addressesThatOpenedThisCampaign['data'] as $open) {                
                $finalListofEmails[] = $open['email'];
            }
            
        } else if ($api->errorCode==301) {
            /*
             * A 301 is the campaign we are looking at has no stats. It
             * probably hasn't been sent yet.
             */
            $continueProcessing = false;
        } else {
            /*
             * Something else happened. Let the user know but keep going.
             */
            outputError("API Error Code:".$api->errorCode."\n\n",$verbose, $argv, $options);
        }
        
        $pageNo++;
        
    } while ($continueProcessing===true);
    
}

/*
 * Now add them to the list segment.
 */
if ($verboseMode) {
    echo "Filtering out duplicate email addresses.\n";
} // if ($verboseMode)

$finalListofEmails = array_unique($finalListofEmails,SORT_STRING);

if ($verboseMode) {
    echo "A total of ".count($finalListofEmails)." emails will be added to the segment.\n";
} // if ($verboseMode)

if (!$testMode) {
    $output = array_chunk($finalListofEmails,1000);
    if (is_null($segmentId)) {    
        $segmentId = $api->listStaticSegmentAdd($listId,$segmentTitle);
        
        if ($verboseMode) {
            echo "Created segment #{$segmentId}.\n";
        } // if ($verboseMode)
        
    }
    
    foreach ($output as $chunk) {
        $api->listStaticSegmentMembersAdd($listId,$segmentId,$chunk);    
    } // foreach ($output as $chunk)
    
} // if (!$testMode)

if ($verboseMode) {
    echo "\nDone!\n";
} // if ($verboseMode)

die(0);

function outputHeader()
{
    echo "\n";
    echo "Mail Chimp List Segment Maker\n";
    echo "  Author: Cal Evans <cal@calevans.com>\n";
    echo "  Blog  : http://blog.calevans.com\n";
    echo "\n";
    return;    
}

function outputError($message='',$verboseMode=false,$argv,$options)
{
    if ($verboseMode) {
        echo "\n\nError:\n";
        echo "======\n";
        echo $message."\n";
        
        echo "Inputs:\n";
        print_r($argv);
        echo "\nOptions:\n";
        print_r($options);
        
    }
}


