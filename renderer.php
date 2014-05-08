<?php


class mod_tadc_renderer extends plugin_renderer_base {
    function display_tadc(stdClass $tadc){

        $context = context_module::instance($tadc->cmid);
        $options = array('noclean' => true, 'para' => false, 'filter' => false, 'context' => $context, 'overflowdiv' => true);
        $output = html_writer::start_div('tadc_citation');
        $output .= format_text($tadc->citation, $tadc->citationformat, $options);
        $output .= html_writer::end_div();
        if($tadc->request_status !== 'LIVE')
        {

            if($tadc->request_status)
            {
                $output .= html_writer::start_div('tadc_status');
                $output .= html_writer::start_tag('strong');
                $output .= format_string($tadc->request_status . ':', true, $options);
                $output .= html_writer::end_tag('strong');
                if($tadc->reason_code)
                {
                    $output .= get_string($tadc->reason_code . 'Message', 'tadc');
                }
                $output .= html_writer::end_div();
            }
        }

        if($tadc->showdescription && trim($tadc->intro) !== '')
        {
            $output .= format_module_intro('tadc', $tadc, $tadc->cmid, false);
        }
        return $output;
        
    }

}