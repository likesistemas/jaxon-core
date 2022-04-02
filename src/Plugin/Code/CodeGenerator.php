<?php

/**
 * CodeGenerator.php - Jaxon code generator
 *
 * Generate HTML, CSS and Javascript code for Jaxon.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Plugin\Code;

use Jaxon\Di\Container;
use Jaxon\Plugin\Plugin;
use Jaxon\Utils\Http\UriException;
use Jaxon\Utils\Template\TemplateEngine;

use function array_reduce;
use function ksort;
use function md5;
use function trim;
use function is_subclass_of;

class CodeGenerator
{
    /**
     * @var string
     */
    private $sVersion;

    /**
     * @var Container
     */
    private $di;

    /**
     * The Jaxon template engine
     *
     * @var TemplateEngine
     */
    protected $xTemplateEngine;

    /**
     * @var AssetManager
     */
    private $xAssetManager;

    /**
     * The class names of objects that generate code
     *
     * @var array<string>
     */
    protected $aClassNames = [];

    /**
     * @var string
     */
    protected $sJsOptions;

    /**
     * @var string
     */
    protected $sCss = '';

    /**
     * @var string
     */
    protected $sJs = '';

    /**
     * @var string
     */
    protected $sJsScript = '';

    /**
     * @var string
     */
    protected $sJsReadyScript = '';

    /**
     * @var string
     */
    protected $sJsInlineScript = '';

    /**
     * @var string
     */
    protected $bGenerated = false;

    /**
     * The constructor
     *
     * @param string $sVersion
     * @param Container $di
     * @param TemplateEngine $xTemplateEngine
     * @param AssetManager $xAssetManager
     */
    public function __construct(string $sVersion, Container $di,
        TemplateEngine $xTemplateEngine, AssetManager $xAssetManager)
    {
        $this->sVersion = $sVersion;
        $this->di = $di;
        $this->xTemplateEngine = $xTemplateEngine;
        $this->xAssetManager = $xAssetManager;
        $this->sJsOptions = $xAssetManager->getJsOptions();
    }

    /**
     * Add a new generator to the list
     *
     * @param string $sClassName    The code generator class
     * @param int $nPriority    The desired priority, used to order the plugins
     *
     * @return void
     */
    public function addGenerator(string $sClassName, int $nPriority)
    {
        while(isset($this->aClassNames[$nPriority]))
        {
            $nPriority++;
        }
        $this->aClassNames[$nPriority] = $sClassName;
        // Sort the array by ascending keys
        ksort($this->aClassNames);
    }

    /**
     * Generate a hash for all the javascript code generated by the library
     *
     * @return string
     */
    public function getHash(): string
    {
        return md5(array_reduce($this->aClassNames, function($sHash, $sClassName) {
            return $sHash . $this->di->g($sClassName)->getHash();
        }, $this->sVersion));
    }

    /**
     * Render a template in the 'plugins' subdir
     *
     * @param string $sTemplate    The template filename
     * @param array $aVars    The template variables
     *
     * @return string
     */
    private function render(string $sTemplate, array $aVars = []): string
    {
        $aVars['sJsOptions'] = $this->sJsOptions;
        return $this->xTemplateEngine->render("jaxon::plugins/$sTemplate", $aVars);
    }

    /**
     * Generate the Jaxon CSS and js codes for a given plugin
     *
     * @param string $sClassName
     *
     * @return void
     */
    private function generatePluginCodes(string $sClassName)
    {
        $xGenerator = $this->di->g($sClassName);
        if(!is_subclass_of($xGenerator, Plugin::class) || $this->xAssetManager->shallIncludeAssets($xGenerator))
        {
            // HTML tags for CSS
            $this->sCss .= trim($xGenerator->getCss(), " \n") . "\n";
            // HTML tags for js
            $this->sJs .= trim($xGenerator->getJs(), " \n") . "\n";
        }
        // Javascript code
        $this->sJsScript .= trim($xGenerator->getScript(), " \n") . "\n";
        if($xGenerator->readyEnabled())
        {
            $sScriptAttr = $xGenerator->readyInlined() ? 'sJsInlineScript' : 'sJsReadyScript';
            $this->$sScriptAttr .= trim($xGenerator->getReadyScript(), " \n") . "\n";
        }
    }

    /**
     * Render the generated CSS ans js codes
     *
     * @return void
     */
    private function renderCodes()
    {
        $this->sCss = trim($this->sCss, " \n");
        $this->sJs = trim($this->sJs, " \n");
        $this->sJsScript = trim($this->sJsScript, " \n");
        $this->sJsReadyScript = trim($this->sJsReadyScript, " \n");
        $this->sJsInlineScript = trim($this->sJsInlineScript, " \n");
        if(($this->sJsReadyScript))
        {
            $this->sJsReadyScript = $this->render('ready.js', ['sScript' => $this->sJsReadyScript . "\n"]);
        }
        if(($this->sJsInlineScript))
        {
            $this->sJsInlineScript = $this->render('ready.js', ['sScript' => $this->sJsInlineScript . "\n"]);
        }
        // Prepend Jaxon javascript files to HTML tags for Js
        $aJsFiles = $this->xAssetManager->getJsLibFiles();
        $this->sJs = $this->render('includes.js', ['aUrls' => $aJsFiles]) . "\n" . $this->sJs;
    }

    /**
     * Generate the Jaxon CSS ans js codes
     *
     * @return void
     */
    private function generateCodes()
    {
        if($this->bGenerated)
        {
            return;
        }

        foreach($this->aClassNames as $sClassName)
        {
            $this->generatePluginCodes($sClassName);
        }
        $this->renderCodes();

        // The codes are already generated.
        $this->bGenerated = true;
    }

    /**
     * Get the HTML tags to include Jaxon CSS code and files into the page
     *
     * @return string
     */
    public function getCss(): string
    {
        $this->generateCodes();
        return $this->sCss;
    }

    /**
     * Get the HTML tags to include Jaxon javascript files into the page
     *
     * @return string
     */
    public function getJs(): string
    {
        $this->generateCodes();
        return $this->sJs;
    }

    /**
     * Get the javascript code to be sent to the browser
     *
     * @param bool $bIncludeJs Also get the JS files
     * @param bool $bIncludeCss Also get the CSS files
     *
     * @return string
     * @throws UriException
     */
    public function getScript(bool $bIncludeJs, bool $bIncludeCss): string
    {
        $this->generateCodes();
        $sScript = '';
        if(($bIncludeCss))
        {
            $sScript .= $this->getCss() . "\n";
        }
        if(($bIncludeJs))
        {
            $sScript .= $this->getJs() . "\n";
        }

        $sJsConfigVars = $this->render('config.js', $this->xAssetManager->getOptionVars());
        // These three parts are always rendered together
        $sJsScript = $sJsConfigVars . "\n" . $this->sJsScript . "\n" . $this->sJsReadyScript;
        if($this->xAssetManager->shallCreateJsFiles() &&
            ($sUrl = $this->xAssetManager->createJsFiles($this->getHash(), $sJsScript)))
        {
            return trim($sScript) . "\n" . $this->render('include.js', ['sUrl' => $sUrl]) . "\n" .
                $this->render('wrapper.js', ['sScript' => $this->sJsInlineScript]);
        }
        return trim($sScript) . "\n" . $this->render('wrapper.js',
            ['sScript' => $sJsScript . "\n" . $this->sJsInlineScript]);
    }
}
