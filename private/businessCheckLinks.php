<?php
//
// Description
// -----------
// This function will setup the javascript for image resize and positioning in gallery view.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function ciniki_linkchecker_businessCheckLinks(&$ciniki, $business_id) {

    //
    // Get the list of modules enabled for the business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'getActiveModules');
    $rc = ciniki_businesses_hooks_getActiveModules($ciniki, $business_id, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $business_modules = array_keys($rc['modules']);

    //
    // Update the modules indexes
    //
    $objects = array();
    foreach($business_modules as $module) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'linkcheckerList');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $business_id, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['objects']) ) {
                $objects = array_merge($objects, $rc['objects']);
            }
        } 
    }

    //
    // Check each object
    //
    error_reporting(E_ERROR);
    foreach($objects as $oid => $object) {
        $rc = get_headers($object['url'], 1);
        $num_redirects = 0;
        $moved_to = '';
        while( $rc !== false && isset($rc['Location']) && $rc['Location'] != '' ) {
            $num_redirects++;
            if( $num_redirects > 10 ) {
                print "Redirect Loop: " . $object['url'] . "\n";
                break;
            }
            $moved_to = $rc['Location'];
            $rc = get_headers($rc['Location'], 1);
        }
        if( $num_redirects > 10 ) {
            continue;
        }
        if( $rc !== false && isset($rc[0]) && preg_match("/HTTP.* ([0-9][0-9][0-9]) (.*)/", $rc[0], $matches) ) {
            print $matches[1] . ': ' . $object['url'] . "\n";
        } else {
            print "???: " . $object['url'] . "\n";
        }
        if( $moved_to != '' ) {
            print "  New Address: " . $moved_to . "\n";
        }
    }
    
    return array('stat'=>'ok');
}
?>
