<?php
//
// Description
// -----------
// This function will return the headers, similar to php function get_headers
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function ciniki_linkchecker_getHeaders(&$ciniki, $tnid, $url) {
    //
    // If instagram, use get_headers instead
    //
    if( strpos('instagram.com', $url) !== false ) {
        $headers = get_headers($url, 1);
    } else {

        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_setopt($handle, CURLOPT_USERAGENT, true);

        $rsp = curl_exec($handle);
        if( $rsp === false ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.linkchecker.6', 'msg'=>'Unable to open url'));
        }
        curl_close($handle);
       
        //
        // Parse the headers into hash
        //
        if (!empty($rsp)){
            $rsp = explode("\n", $rsp);
            foreach($rsp as $header) {
                if( $header == '' ) {
                    continue;
                }
                $pieces = explode(': ', $header, 2);
                if( count($pieces) == 2 ) {
                    $headers[$pieces[0]] = preg_replace("/[\r\n]/", '', $pieces[1]);
                } else {
                    $headers[] = preg_replace("/[\r\n]/", '', $header);
                }
            }
        }
    }

    return array('stat'=>'ok', 'headers'=>$headers);
}
?>
