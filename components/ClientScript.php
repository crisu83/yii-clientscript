<?php
/**
 * ClientScript class file.
 * @author Christoffer Niska <christoffer.niska@gmail.com>
 * @copyright Copyright &copy; Christoffer Niska 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package crisu83.yii-clientScript.components
 */

/**
 * Client script component with additional functionality such as ordering of files
 * and allowing to push new versions of files to the users when they have change.
 */
class ClientScript extends CClientScript
{
    public $skipOnAjax = array();

    /**
     * @var array the desired order for registering css files.
     */
    public $cssFileOrder = array();

    /**
     * @var array the desired order for registering script files.
     */
    public $scriptFileOrder = array();

    /**
     * Inserts the scripts in the head section.
     * @param string $output the output to be inserted with scripts.
     */
    public function renderHead(&$output)
    {
        $this->filterScripts();
        $this->reorderFiles();
        $this->processFiles();
        parent::renderHead($output);
    }

    protected function filterScripts()
    {
        if (Yii::app()->request->isAjaxRequest) {
            foreach ($this->skipOnAjax as $filename) {
                $script = $this->findScriptFile($filename);
                if ($script !== false) {
                    list($position, $url) = $script;
                    unset($this->scriptFiles[$position][$url]);
                }
            }
        }
    }

    /**
     * Re-orders the css and script files according the order given in cssOrder and scriptOrder.
     */
    protected function reorderFiles()
    {
        $tmp = array();
        foreach ($this->cssFileOrder as $filename) {
            $css = $this->findCssFile($filename);
            if ($css !== false) {
                list($url, $media) = $css;
                if (isset($url, $media)) {
                    $tmp[$url] = $media;
                }
                unset($this->cssFiles[$url]);
            }
        }
        foreach ($tmp as $url => $media) {
            $this->registerCssFile($url, $media);
        }
        $tmp = array();
        foreach ($this->scriptFileOrder as $filename) {
            $script = $this->findScriptFile($filename);
            if ($script !== false) {
                list($position, $url) = $script;
                if (isset($position, $url)) {
                    if (!isset($tmp[$position])) {
                        $tmp[$position] = array();
                    }
                    $tmp[$position][$url] = $url;
                }
                unset($this->scriptFiles[$position][$url]);
            }
        }
        foreach ($tmp as $position => $urls) {
            foreach ($urls as $url) {
                $this->registerScriptFile($url, $position);
            }
        }
    }

    /**
     * Returns the position and url for the css file with the given filename.
     * @param string $filename the filename.
     * @return array|bool an array with url and media or false if not found.
     */
    protected function findCssFile($filename)
    {
        foreach ($this->cssFiles as $url => $media) {
            if ($filename === $this->resolveFilename($url)) {
                return array($url, $media);
            }
        }
        return false;
    }

    /**
     * Returns the position and url for the script file with the given filename.
     * @param string $filename the filename.
     * @return array|bool an array with position and url or false if not found.
     */
    protected function findScriptFile($filename) {
        foreach ($this->scriptFiles as $position => $urls) {
            foreach ($urls as $url) {
                if ($filename === $this->resolveFilename($url)) {
                    return array($position, $url);
                }
            }
        }
        return false;
    }

    /**
     * Returns the filename for the given url.
     * @param string $url the url.
     * @return string the filename.
     */
    protected function resolveFilename($url)
    {
        return substr($url, strrpos($url, '/') + 1);
    }

    /**
     * Process the files in cssFiles and scriptFiles by appending the cache buster to their url.
     */
    protected function processFiles()
    {
        $cssFiles = array();
        foreach ($this->cssFiles as $url => $media) {
            $cssFiles[$this->appendCacheBuster($url)] = $media;
        }
        $this->cssFiles = $cssFiles;
        foreach ($this->scriptFiles as $position => $urls) {
            foreach ($urls as $url) {
                unset($this->scriptFiles[$position][$url]);
                $url = $this->appendCacheBuster($url);
                $this->scriptFiles[$position][$url] = $url;
            }
        }
    }

    /**
     * Returns the file path for the given url.
     * @param string $url the url.
     * @return string the path.
     */
    protected function resolveFilePath($url)
    {
        $baseUrl = Yii::app()->request->baseUrl;
        if (!empty($baseUrl) && strpos($url, $baseUrl) === false) {
            return false;
        } // not a local file
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $url;
        return file_exists($filePath) ? $filePath : false;
    }

    /**
     * Appends the modified the given file to its url.
     * @param string $url the url.
     * @return string the url.
     */
    protected function appendCacheBuster($url)
    {
        if (($filePath = $this->resolveFilePath($url)) !== false) {
            $modified = filemtime($filePath);
            $url .= '?_=' . md5($modified);
        }
        return $url;
    }
}