<?php
//
// Description
// -----------
// This script will hook into the other modules and check for links, then check the link status.
//

//
// Initialize Moss by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbQuote.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbHashQuery.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbHashQueryIDTree.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

//
// Load the businesses that have linkchecker enabled
//
$strsql = "SELECT b.id, b.name "
    . "FROM ciniki_business_modules AS m "
    . "INNER JOIN ciniki_businesses AS b ON ("
        . "m.business_id = b.id "
        . ") "
    . "WHERE m.package = 'ciniki' "
    . "AND m.module = 'linkchecker' "
    . "AND (m.status = 1 || m.status = 2) "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.linkchecker', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.linkchecker.4', 'msg'=>'', 'err'=>$rc['err']));
}
$businesses = isset($rc['rows']) ? $rc['rows'] : array();

//
// Run the linkcheck for each business
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'linkchecker', 'private', 'businessCheckLinks');
foreach($businesses as $business) {
    $rc = ciniki_linkchecker_businessCheckLinks($ciniki, $business['id']);
    if( $rc['stat'] != 'ok' ) {
        print "LINKCHECKER-ERR: Failed to run for " . $business['name'] . " Error #" . $rc['err']['code'] . ": " . $rc['err']['msg'] . "\n";
        
    }
}

exit(0);
?>
