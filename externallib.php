<?php

/**
 * PLUGIN external file
 *
 * @package    local_PLUGIN
 * @copyright  20XX YOURSELF
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once(dirname(__FILE__).'/lib.php');

class local_tadc_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function trackback_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters().
        // The external_function_parameters constructor expects an array of external_description.
        return new external_function_parameters(
        // a external_description can be: external_value, external_single_structure or external_multiple structure
            array(
                'itemUri' => new external_value(PARAM_INT, 'The ID of the Digitisation Request Resource'),
                'request' => new external_value(PARAM_TEXT, 'The ID of the TADC request'),
                'status' => new external_value(PARAM_TEXT, 'The status of the request'),
                'key' => new external_value(PARAM_TEXT, 'The encoded key'),
                'bundleId' => new external_value(PARAM_TEXT, 'The ID of the Bundle', 0)
            )
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function trackback($itemUri, $request, $status, $key, $bundleId=NULL) {
        global $DB;
        //Parameters validation
        $params = self::validate_parameters(self::trackback_parameters(),
            array('itemUri' => $itemUri, 'request'=>$request, 'status'=>$status, 'key'=>$key, 'bundleId'=>$bundleId));

        $tadc_cfg = get_config('tadc');
        $hmac_data = $itemUri.'|'.$request.'|'.$status;
        if (!empty($bundleId))
        {
            $hmac_data .= '|'.$bundleId;
        }

        $localKey = hash_hmac('sha256', $hmac_data, $tadc_cfg->tadc_shared_secret);
        if($localKey === $key)
        {
            $resource = $DB->get_record('tadc', array('id'=>$itemUri));
            if(!isset($resource->tadc_id))
            {
                $resource->tadc_id = $request;
            }
            $resource->request_status = $status;
            if($bundleId)
            {
                $resource->bundle_url = $bundleId;
            }
            $resource->name = tadc_build_title_string($resource);
            //Note: don't forget to validate the context and check capabilities
            return $DB->update_record('tadc', $resource);
        } else {
            error_log('Error: ' . $key . ' sent, ' . $localKey . ' expected');
            return false;
        }
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function trackback_returns() {
        return new external_value(PARAM_BOOL, 'TADC service trackback endpoint result');
    }



}