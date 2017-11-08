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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'getActiveModules');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'linkchecker', 'private', 'getHeaders');

    //
    // Get the list of modules enabled for the business
    //
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
    // Load existing objects
    //
    $strsql = "SELECT id, "
        . "CONCAT_WS('.', object, object_id) AS oid, "
        . "object, "
        . "object_id, "
        . "url, "
        . "new_url, "
        . "http_status, "
        . "last_http_status, "
        . "last_check, "
        . "num_errors "
        . "FROM ciniki_linkchecker_urls "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.linkchecker', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('id', 'object', 'object_id', 
            'url', 'new_url', 'http_status', 'last_http_status', 'last_check', 'num_errors')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.linkchecker.5', 'msg'=>'Unable to lookup existing objects', 'err'=>$rc['err']));
    }
    $existing_objects = isset($rc['objects']) ? $rc['objects'] : array();

    //
    // Check each object
    //
    error_reporting(E_ERROR);
    $dt = new DateTime('now', new DateTimezone('UTC'));
    foreach($objects as $oid => $object) {
        //
        // Keep track of updates
        //
        if( isset($existing_objects[$oid]) ) {
            $obj = $existing_objects[$oid];
            if( $object['url'] != $obj['url'] ) {
                $obj['url'] = $object['url'];
                $obj['new_url'] = '';
            }
            $obj['last_check'] = $dt->format('Y-m-d H:i:s');
        } else {
            $obj = array(
                'object' => $object['object'],
                'object_id' => $object['object_id'],
                'url' => $object['url'],
                'http_status' => 0,
                'last_http_status' => 0,
                'last_check' => $dt->format('Y-m-d H:i:s'),
                'num_errors' => 0,
                );
        }
        $rc = ciniki_linkchecker_getHeaders($ciniki, $business_id, $object['url']);
        if( $rc['stat'] == 'ok' && preg_match('/facebook.com/', $object['url']) && preg_match("/HTTP.* 302 Found/", $rc['headers'][0], $matches) ) {
            error_log('facebook: ' . $object['url']);
            $obj['http_status'] = 200;
            $obj['new_url'] = '';
        } elseif( $rc['stat'] == 'ok' && isset($rc['headers'][0]) && preg_match("/HTTP.* ([0-9][0-9][0-9]) (.*)/", $rc['headers'][0], $matches) ) {
            $obj['http_status'] = $matches[1];
        } elseif( $rc['stat'] == 'fail' ) {
            $obj['http_status'] = 80;
        } else {
            $obj['http_status'] = 90;
        }
        $num_redirects = 0;
        while( $rc['stat'] != 'fail' && $obj['http_status'] != 200 && isset($rc['headers']['Location']) && $rc['headers']['Location'] != '' ) {
            error_log('redirect');
            $num_redirects++;
            if( $num_redirects > 10 ) {
                //
                // 
                $obj['http_status'] = 70;
                print "Redirect Loop: " . $object['url'] . "\n";
                break;
            }
            $obj['new_url'] = $rc['headers']['Location'];
            $rc = ciniki_linkchecker_getHeaders($ciniki, $business_id, $rc['headers']['Location']);
        }
        if( $num_redirects > 10 ) {
            continue;
        }

        //
        // Update/Add the object
        //
        if( isset($existing_objects[$oid]) ) {
            $updates = array();
            foreach(['url', 'new_url', 'http_status', 'last_http_status', 'last_check', 'num_errors'] as $field) {
                if( $obj[$field] != $existing_objects[$oid][$field] ) {
                    $updates[$field] = $obj[$field];
                }
            }
            if( count($updates) > 0 ) {
                print_r($updates);
                $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.linkchecker.url', $existing_objects[$oid]['id'], $updates, 0x04);
                if( $rc['stat'] != 'ok' && $rc['stat']) {
                    return $rc;
                }
            }
        } else {    
            print_r($obj);
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.linkchecker.url', $obj, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }
    
    return array('stat'=>'ok');
}
?>
