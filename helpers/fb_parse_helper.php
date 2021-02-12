<?php if (!defined('BASEPATH')) exit('No direct script access allowed.');


/**
 * Create rows for the EE parser
 *
 * Some FB data is an array that is not indexed. For example the from data is an associative array.  THe EE parser needs a "row" to work with. This function will recursively work through the data and if an array is not indexed will create the index. It's a beast of a function but necessary for now and should be flexible enough to cope with FB structure changes.
 *
 * @param $array
 * @param null $parent
 *
 * @return array
 */
function make_rows($array, $parent = NULL)
{
    $var = array();

    // Work through each item to catch arrays and format them for parsing
    foreach ($array as $k => $v) {

        // Not an array so pass over to the formatting function after adding the parent key to the current key.
        // This is necessary for the EE parser which trips when keys have the same name - regardless of array context.
        if (!is_array($v) && $parent != NULL) {
            $var[$parent . ':' . $k] = format($k, $v);

            // Create a permalink from post id if the key is id and it matches the pattern
            if ($k == 'id' && (preg_match('/^\d+_{1}\d+$/', $v)) === 1) {
                $p = explode('_', $v);
                $var[$parent . ':permalink'] = 'http://www.facebook.com/' . $p[0] . '/posts/' . $p[1];
                unset($p);
            }

        } elseif (!is_array($v)) {
            $var[$k] = format($k, $v);

        // Is an array so hold up we need some more work.
        } elseif (is_array($v)) {

            // We need to rename the "row" named data based on it's parent or else the parser gets confused. Here we rename it and merge the array up to eliminate any unnecessary tag.
            if (isset($v['data']) && !is_numeric(current(array_keys($v['data'])))) {
                $v[$k . ':data'][] = $v['data'];
                unset($v['data']);
            } elseif (isset($v['data']) && is_numeric(current(array_keys($v['data'])))) {
                $v[$k . ':data'] = $v['data'];
                unset ($v['data']);
            }

            // If it's not numeric we need to create that.
            if (!is_numeric($k) && !is_numeric(current(array_keys($v)))) {

                if ($parent != NULL) {
                    $var[$parent . ':' . $k][0] = make_rows($v, $k);
                } else {
                    $var[$k][0] = make_rows($v, $k);
                }
            } else {
                $var[$k] = make_rows($v, $parent);
            }
        }
    }

    return $var;
}


/**
 * Formatter for auto links
 * @param $k
 * @param $v
 *
 * @return mixed
 */
function format($k, $v)
{
    if (($k == 'message') || ($k == 'story')) {
    // As of January 2013 'auto_links' is set to no.  The Graph now returns formatted links inside of posts.  If links are causing problems set 'auto_links' to yes and see if that corrects the issue.
        $v = auto_link(ee()->typography->parse_type($v, array('text_format' => 'lite', 'html_format' => 'safe', 'auto_links' => 'n')));
    }

    return $v;
}

/* End of file */
/* Location: ./system/user/addons/fb_link/helpers/fb_parse_helper.php */