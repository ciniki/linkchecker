<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_linkchecker_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['url'] = array(
        'name' => 'URL',
        'sync' => 'yes',
        'o_name' => 'url',
        'o_container' => 'urls',
        'table' => 'ciniki_linkchecker_urls',
        'fields' => array(
            'object' => array('name'=>'Object'),
            'object_id' => array('name'=>'Object ID'),
            'url' => array('name'=>''),
            'new_url' => array('name'=>'', 'default'=>''),
            'http_status' => array('name'=>'', 'default'=>10),
            'last_http_status' => array('name'=>'', 'default'=>0),
            'last_check' => array('name'=>''),
            'num_errors' => array('name'=>'', 'default'=>'0'),
            ),
        'history_table' => 'ciniki_linkchecker_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
