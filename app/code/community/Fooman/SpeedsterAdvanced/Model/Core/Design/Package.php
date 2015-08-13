<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @copyright  Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com) (original implementation)
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz) (use of Minify Library)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/*
 * @author     Kristof Ringleff
 * @package    Fooman_SpeedsterAdvanced
 * @copyright  Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 */

set_include_path(BP . DS . 'lib' . DS . 'minify' . PS . get_include_path());

class Fooman_SpeedsterAdvanced_Model_Core_Design_Package extends Mage_Core_Model_Design_Package
{

    protected $_speedsterBlacklists = array();
    protected $_speedsterMergeFilesMethod = array();

    public function __construct()
    {
        if (method_exists('Mage_Core_Model_Design_Package', '__construct')) {
            parent::__construct();
        }
        foreach (explode(',', Mage::getStoreConfig('dev/js/speedster_minify_blacklist')) as $jsBlacklist) {
            $jsBlacklist = Mage::helper('speedsterAdvanced')->normaliseUrl($jsBlacklist);
            if ($jsBlacklist) {
                $this->_speedsterBlacklists['js']['minify'][$jsBlacklist] = true;
            }
        }
        foreach (explode(',', Mage::getStoreConfig('dev/css/speedster_minify_blacklist')) as $cssBlacklist) {
            $cssBlacklist = Mage::helper('speedsterAdvanced')->normaliseUrl($cssBlacklist);
            if ($cssBlacklist) {
                $this->_speedsterBlacklists['css']['minify'][$cssBlacklist] = true;
            }
        }
        foreach (explode(',', Mage::getStoreConfig('dev/css/speedster_minify_blacklist_secure')) as $cssBlacklist) {
            $cssBlacklist = Mage::helper('speedsterAdvanced')->normaliseUrl($cssBlacklist);
            if ($cssBlacklist) {
                $this->_speedsterBlacklists['css_secure']['minify'][$cssBlacklist] = true;
            }
        }
        if (method_exists($this, '_mergeFiles')) {
            $this->_speedsterMergeFilesMethod = array($this, '_mergeFiles');
        } else {
            $this->_speedsterMergeFilesMethod = array(Mage::helper('core'), 'mergeFiles');
        }
    }


    /**
     * Merge specified JS files and return URL to the merged file on success
     * filename is md5 of files + timestamp of last modified file
     *
     * @param string $files
     *
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $jsBuild = Mage::getModel('speedsterAdvanced/buildSpeedster')->__construct($files, BP);
        $targetFilename = md5(implode(',', $files)) . '-' . $jsBuild->getLastModified() . '.js';
        if (file_exists(Mage::getBaseDir('media') . '/js/' . $targetFilename)) {
            return Mage::getBaseUrl('media') . 'js/' . $targetFilename;
        }
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        if (call_user_func(
            $this->_speedsterMergeFilesMethod,
            $files,
            $targetDir . DS . $targetFilename,
            false,
            array($this, 'beforeMergeJs'),
            'js'
        )) {
            return Mage::getBaseUrl('media') . 'js/' . $targetFilename;
        }
        return '';
    }


    /**
     * Before merge JS callback function
     *
     * @param string $file
     * @param string $contents
     *
     * @return string
     */
    public function beforeMergeJs($file, $contents)
    {
        //append full content of blacklisted files
        $relativeFileName = str_replace(BP . DS, '', $file);
        if (isset($this->_speedsterBlacklists['js']['minify'][$relativeFileName])) {
            if (Mage::getIsDeveloperMode()) {
                return "\n/*" . $file . " (original) */\n" . $contents . "\n\n";
            }
            return "\n" . $contents;
        }

        if (preg_match('/@ sourceMappingURL=([^\s]*)/s', $contents, $matches)) {
            //create a file without source map
            $contents = str_replace(
                $matches[0], '',
                $contents
            );
        }

        if (Mage::getIsDeveloperMode()) {
            return
                "\n/*" . $file . " (minified) */\n" . Mage::getModel('speedsterAdvanced/javascript')->minify($contents)
                . "\n\n";
        }

        return "\n" . Mage::getModel('speedsterAdvanced/javascript')->minify($contents);
    }

    /**
     * Merge specified css files and return URL to the merged file on success
     * filename is md5 of files + storeid + SSL flag + timestamp of last modified file
     *
     * @param $files
     *
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        $cssBuild = Mage::getModel('speedsterAdvanced/buildSpeedster')->__construct($files, BP);
        $targetDir = $this->_initMergerDir('css');
        if (!$targetDir) {
            return '';
        }
        $storeId = Mage::app()->getStore()->getId();
        if (Mage::app()->getStore()->isCurrentlySecure()) {
            $targetFilename
                = md5(implode(',', $files)) . '-' . $storeId . '-SSL-' . $cssBuild->getLastModified() . '.css';
            if (file_exists(Mage::getBaseDir('media') . '/css_secure/' . $targetFilename)) {
                return Mage::getBaseUrl('media') . 'css_secure/' . $targetFilename;
            }
            if (call_user_func(
                $this->_speedsterMergeFilesMethod,
                $files,
                $targetDir . DS . $targetFilename,
                false,
                array($this, 'beforeMergeCssSecure'),
                'css'
            )) {
                return Mage::getBaseUrl('media') . 'css/' . $targetFilename;
            }
        } else {
            $targetFilename = md5(implode(',', $files)) . '-' . $storeId . '-' . $cssBuild->getLastModified() . '.css';
            if (file_exists(Mage::getBaseDir('media') . '/css/' . $targetFilename)) {
                return Mage::getBaseUrl('media') . 'css/' . $targetFilename;
            }
            if (call_user_func(
                $this->_speedsterMergeFilesMethod,
                $files,
                $targetDir . DS . $targetFilename,
                false,
                array($this, 'beforeMergeCss'),
                'css'
            )) {
                return Mage::getBaseUrl('media') . 'css/' . $targetFilename;
            }
        }
        return '';
    }

    /**
     * Before merge css callback function
     *
     * @param string $origFile
     * @param string $contents
     *
     * @return string
     */
    public function beforeMergeCss($origFile, $contents)
    {
        //append full content of blacklisted files
        $relativeFileName = str_replace(BP . DS, '', $origFile);
        if (isset($this->_speedsterBlacklists['css']['minify'][$relativeFileName])) {
            if (Mage::getIsDeveloperMode()) {
                return "\n/* NON-SSL:" . $origFile . " (original) */\n" . $contents . "\n\n";
            }
            return "\n" . $contents;
        }

        //make file relative to Magento root
        //assumes files are under Magento root
        $file = str_replace(BP, '', $origFile);

        //we have some css residing in the js folder
        $filePathComponents = explode(DS, $file);
        $isJsPath = $filePathComponents[1] == 'js';

        //drop filename from end
        array_pop($filePathComponents);

        //remove first empty and skin or js from start
        array_shift($filePathComponents);
        array_shift($filePathComponents);

        if ($isJsPath) {
            $jsPath = implode(DS, $filePathComponents);
            $prependRelativePath = Mage::getStoreConfig('web/unsecure/base_js_url') . $jsPath . DS;
        } else {
            $skinPath = implode(DS, $filePathComponents);
            $prependRelativePath = Mage::getStoreConfig('web/unsecure/base_skin_url') . $skinPath . DS;
        }

        //we might be on windows but instructions in layout updates use / as directory separator
        if (DS != '/') {
            $origFile = str_replace('/', DS, $origFile);
        }
        $completeFilePathComponents = explode(DS, $origFile);
        //drop filename from end
        array_pop($completeFilePathComponents);

        $options = array(
            // currentDir overrides prependRelativePath
            //'currentDir'         => implode(DS, $completeFilePathComponents),
            'preserveComments'    => false,
            'prependRelativePath' => $prependRelativePath,
            'symlinks'            => array('//' => BP)
        );

        if (Mage::getIsDeveloperMode()) {
            return "\n/* NON-SSL: " . $origFile . " (minified)  */\n" . $this->_returnMergedCss($contents, $options)
            . "\n\n";
        }
        return $this->_returnMergedCss($contents, $options);
    }

    /**
     * Before merge css callback function (secure)
     *
     * @param string $origFile
     * @param string $contents
     *
     * @return string
     */
    public function beforeMergeCssSecure($origFile, $contents)
    {
        //append full content of blacklisted files
        $relativeFileName = str_replace(BP . DS, '', $origFile);
        if (isset($this->_speedsterBlacklists['css_secure']['minify'][$relativeFileName])) {
            if (Mage::getIsDeveloperMode()) {
                return "\n/* NON-SSL:" . $origFile . " (original) */\n" . $contents . "\n\n";
            }
            return "\n" . $contents;
        }

        //make file relative to Magento root
        //assumes files are under Magento root
        $file = str_replace(BP, '', $origFile);

        //we have some css residing in the js folder
        $filePathComponents = explode(DS, $file);
        $isJsPath = $filePathComponents[1] == 'js';

        //drop filename from end
        array_pop($filePathComponents);

        //remove first empty and skin or js from start
        array_shift($filePathComponents);
        array_shift($filePathComponents);

        if ($isJsPath) {
            $jsPath = implode(DS, $filePathComponents);
            $prependRelativePath = Mage::getStoreConfig('web/secure/base_js_url') . $jsPath . DS;
        } else {
            $skinPath = implode(DS, $filePathComponents);
            $prependRelativePath = Mage::getStoreConfig('web/secure/base_skin_url') . $skinPath . DS;
        }
        //we might be on windows but instructions in layout updates use / as directory separator
        if (DS != '/') {
            $origFile = str_replace('/', DS, $origFile);
        }
        $completeFilePathComponents = explode(DS, $origFile);
        //drop filename from end
        array_pop($completeFilePathComponents);

        $options = array(
            // currentDir overrides prependRelativePath
            //'currentDir'         => implode(DS, $completeFilePathComponents),
            'preserveComments'    => false,
            'prependRelativePath' => $prependRelativePath,
            'symlinks'            => array('//' => BP)
        );
        if (Mage::getIsDeveloperMode()) {
            return "\n/* SSL: " . $origFile . " (minified) */\n" . $this->_returnMergedCss($contents, $options);
        }
        return $this->_returnMergedCss($contents, $options);
    }

    /**
     * return minified output
     *
     * @param $contents
     * @param $options
     *
     * @return string
     */
    private function _returnMergedCss($contents, $options)
    {
        return "\n" . Mage::getModel('speedsterAdvanced/css')->minify($contents, $options);
    }

}
