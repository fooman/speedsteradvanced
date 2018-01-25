<?php

require_once 'minify' . DS . 'Minify' . DS . 'HTML.php';

class Fooman_SpeedsterAdvanced_Model_Html extends Minify_HTML
{
    protected function _commentCB($m)
    {
        // if the parent method would keep the comment, we keep it as well (IE conditional comments)
        $parentResult = parent::_commentCB($m);
        if ($parentResult !== '') {
            return $parentResult;
        }

        // keep comments, which are used by full page caching plugins for holepunching!
        // Lesti_Fpc uses the placeholder "<!-- fpc"
        // Nexcessnet_Turpentine uses the placeholder "<!-- ESI" and "<!--esi"
        if (0 === strpos($m[1], ' fpc') || 0 === strpos($m[1], ' ESI') || 0 === strpos($m[0], '<!--esi')) {
            return $m[0];
        }

        return '';
    }
}
