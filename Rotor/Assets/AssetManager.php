<?php
namespace Rotor\Assets;

use Rotor\Assets\Compiler\CompilerInterface;
use Rotor\Assets\Exception\CyclicDependencyException;
use Rotor\Assets\Minifier\MinifierInterface;

class AssetManager
{
    /**
     * @var AssetsConfig
     */
    public $config;

    /**
     * @var CompilerInterface[]
     */
    public $compilers = [];
    /**
     * @var MinifierInterface[]
     */
    public $minifiers = [];

    /**
     * @var AssetDefinition[]
     */
    protected $declaredDefinitions = [];

    /**
     * @var ExposedAssetDefinition[]
     */
    protected $definitions = [];
    /**
     * @var Asset[]
     */
    protected $assets = [];
    protected $dependencies = [];
    protected $asset_order = [];


    protected $output_prepared = false;

    protected $requests = [];

    protected $result_head = '';
    protected $result_bottom = '';


    public function __construct(AssetsConfig $config)
    {
        $this->config = $config;
        $this->combine = $this->config->combine;
        $this->minify = $this->config->minify;
        $this->gzip = $this->config->gzip;

        //self::loadAssets();
    }

    public function config()
    {
        return $this->config();
    }

    public function addDefinition(AssetDefinition $definition)
    {
        $this->declaredDefinitions[$definition->name()] = $definition;
    }

    /**
     * @param CompilerInterface $compiler
     */
    public function registerCompiler(CompilerInterface $compiler)
    {
        if (!array_key_exists($compiler->getOutputExtension(), $this->compilers)) {
            $this->compilers[$compiler->getOutputExtension()] = [];
        }
        $this->compilers[$compiler->getOutputExtension()][] = $compiler;
    }

    /**
     * @param CompilerInterface $minifier
     */
    public function registerMinifier(MinifierInterface $minifier)
    {
        $this->minifiers[$minifier->getExtension()] = $minifier;
    }

    public function require_asset($assetName)
    {
        $this->requests[] = $assetName;
    }


    protected function buildDependencyTrees($name = null, $previous = [], $inHead = false)
    {
        $head = [];
        $bottom = [];
        if ($name !== null) {
            $previous[] = $name;
            $def = $this->definitions[$name];
            $deps = $def->dependencies;
        } else {
            $deps = $this->requests;
        }

        foreach ($deps as $dep) {
            if (in_array($dep, $previous)) {
                throw new CyclicDependencyException(sprintf("%s cannot be a dependency of %s - Would create a depencency loop.", $dep, $name));
            }
            $subDeps = $this->buildDependencyTrees($dep, $previous, ($inHead || $this->definitions[$dep]->inHead));
            $head = array_merge($head, $subDeps['head']);
            if ($inHead) {
                $head = array_merge($head, $subDeps['bottom']);
            } else {
                $bottom = array_merge($bottom, $subDeps['bottom']);
            }


        }
        if ($name !== null) {
            if ($def->inHead) {
                $head[] = $name;
            } else {
                $bottom[] = $name;
            }
        }
        $result = [
            'head' => $head,
            'bottom' => $bottom
        ];
        return $result;
    }

    protected function generateMissingDefinitions()
    {
        foreach ($this->declaredDefinitions as $name => $def) {
            foreach ($def->requires() as $req) {
                if (!array_key_exists($req, $this->declaredDefinitions)) {
                    $this->declaredDefinitions[$req] = new AssetDefinition($req);
                }
            }
        }
    }

    protected function exposeDefinitions()
    {
        foreach ($this->declaredDefinitions as $key=>$def){
            $this->definitions[$key] = $this->declaredDefinitions[$key]->retreiveData();
        }
    }


    protected function prepareOutput()
    {
        if ($this->output_prepared) {
            return;
        }
        _g('prepare output');

        $this->generateMissingDefinitions();
        $this->exposeDefinitions();

        $assetsOrder = $this->buildDependencyTrees();
        $assetsOrder['head'] = array_values(array_unique($assetsOrder['head']));
        $assetsOrder['bottom'] = array_values(array_unique(array_diff($assetsOrder['bottom'], $assetsOrder['head'])));

        _d($assetsOrder);

        $headStyles = [];
        $headScripts = [];
        $bottomScripts = [];

        foreach ($assetsOrder['head'] as $name) {
            if ($this->definitions[$name]->type == AssetType::STYLE) {
                $headStyles[] = $name;
            } else {
                $headScripts[] = $name;
            }
        }
        foreach ($assetsOrder['bottom'] as $name) {
            if ($this->definitions[$name]->type == AssetType::STYLE) {
                $headStyles[] = $name;
            } else {
                $bottomScripts[] = $name;
            }
        }

        _d($headStyles, 'head styles');
        _d($headScripts, 'head scripts');
        _d($bottomScripts, 'bottom scripts');

        $headStylesGroups = [];
        $assetGroup = new OutputGroup();
        foreach ($headStyles as $name) {
            $newAsset = new Asset($this->definitions[$name], $this);

        }
    }


    protected function prepareOutput_old()
    {
        if ($this->output_prepared) {
            return;
        }
        $this->output_prepared = true;
        self::calculateDependencies();

        //Now that demendenices are calculated, it's time to do the heavy lifting:

        // have all assets find their source;
        foreach ($this->asset_order as $assetName) {
            $this->assets[$assetName]->findSource();
        }


        //calculate what to place on the head, and what on the body
        //styles always go in the head


        $headStyles = [];
        $headScripts = [];
        $bottomScripts = [];

        $assetCount = count($this->asset_order);
        for ($i = $assetCount - 1; $i >= 0; $i--) {
            $assetName = $this->asset_order[$i];
            $asset = $this->assets[$assetName];

            if ($asset->getType() == Asset::STYLE) {
                $headStyles[] = $assetName;
            } else if ($asset->getType() == Asset::SCRIPT) {
                if ($asset->inHead() || count($headScripts) > 0) {
                    $headScripts[] = $assetName;
                } else {
                    $bottomScripts[] = $assetName;
                }
            }
        }

        $headStyles = array_reverse($headStyles);
        $headScripts = array_reverse($headScripts);
        $bottomScripts = array_reverse($bottomScripts);

        if ($this->combine) {
            $this->result_head = self::combineOutputGroups($headStyles, Asset::STYLE);
            $this->result_head .= self::combineOutputGroups($headScripts, Asset::SCRIPT);
            $this->result_bottom = self::combineOutputGroups($bottomScripts, Asset::SCRIPT);
        } else {
            $this->result_head = self::prepareUncombined($headStyles, Asset::STYLE);
            $this->result_head .= self::prepareUncombined($headScripts, Asset::SCRIPT);
            $this->result_bottom = self::prepareUncombined($bottomScripts, Asset::SCRIPT);
        }
    }


    protected function prepareUncombined($assetList, $type)
    {
        if ($type == Asset::STYLE) { //Style
            $outputPathTemplate = $this->config->outputPath . '/' . $type . '/{filename}';
            $inlineTeplate = '<style>{contents}</style>';
            $declarationTemplate = '<link media="screen" type="text/css" rel="stylesheet" href="{assetName}"/>';
        } elseif ($type == Asset::SCRIPT) { //Script
            $outputPathTemplate = $this->config->outputPath . '/' . $type . '/{filename}';
            $inlineTeplate = '<script type="text/javascript">{contents}</script>';
            $declarationTemplate = '<script type="text/javascript" src="{assetName}"></script>';
        }

        $result = '';

        foreach ($assetList as $assetName) {
            /**@var Asset $asset */
            $asset = $this->assets[$assetName];
            if ($asset->isExternal()) {
                $result .= str_replace('{assetName}', $assetName, $declarationTemplate);
            } else if ($asset->isInline()) {
                $asset->load();
                if ($this->minify && $asset->canMinify()) {
                    $asset->minify();
                }
                $result .= str_replace('{contents}', $asset->getData(), $inlineTeplate);
            } else {
                $assetTime = $asset->getSourceTime();
                $assetPath = str_replace('{filename}', $assetName, $outputPathTemplate);
                if (!file_exists($assetPath) || filemtime($assetPath) < $assetTime || $this->config->forceRecompile) {
                    $asset->load();
                    if ($this->minify && $asset->canMinify()) {
                        $asset->minify();
                    }
                    if (!$asset->isDynamic()) {
                        self::writeFile($assetPath, $asset->getData());
                    }
                    if ($this->gzip) {
                        self::writeFile($assetPath . '.gz', gzencode($asset->getData())); //write the compressed version
                    }
                }
                $result .= str_replace('{assetName}', self::unroot(str_replace('{filename}', $assetName . ($asset->isDynamic() ? ('?v=' . time()) : ''), $outputPathTemplate)), $declarationTemplate);
            }
        }
        return $result;
    }

    protected function combineOutputGroups($assetList, $type)
    {
        //assume asset type is SCRIPT
        if ($type == Asset::SCRIPT) {
            $outputPathTemplate = $this->config->outputPath . '/cache/{filename}.js';
            $inlineTeplate = '<script type="text/javascript">{contents}</script>';
            $declarationTemplate = '<script type="text/javascript" src="{assetName}"></script>';
        } elseif ($type == Asset::STYLE) {
            $outputPathTemplate = $this->config->outputPath . '/cache/{filename}.css';
            $inlineTeplate = '<style>{contents}</style>';
            $declarationTemplate = '<link media="screen" type="text/css" rel="stylesheet" href="{assetName}"/>';
        }

        $result = '';

        $assetGroups = [];
        $groupIndex = 0;
        foreach ($assetList as $assetName) {
            $asset = $this->assets[$assetName];
            if ($asset->canCombine() && !$asset->isExternal() && !$asset->isInline()) {
                if (!isset($assetGroups[$groupIndex])) {
                    $assetGroups[$groupIndex] = [];
                }
                $assetGroups[$groupIndex][] = $assetName;
            } else {
                $groupIndex++;
                $assetGroups[$groupIndex] = $assetName;
                $groupIndex++;
            }
        }


        foreach ($assetGroups as $group) {
            if (is_array($group)) {
                $groupTime = 0;
                foreach ($group as $assetName) {
                    $assetTime = $this->assets[$assetName]->getSourceTime();
                    if ($assetTime > $groupTime) {
                        $groupTime = $assetTime;
                    }
                }
                $groupFileName = self::calculateCombinedName($group, $groupTime);
                $assetPath = str_replace('{filename}', $groupFileName, $outputPathTemplate);

                if (!file_exists($assetPath)) {
                    $contents = '';
                    foreach ($group as $assetName) {
                        $this->assets[$assetName]->load();
                        if ($this->minify && $this->assets[$assetName]->canMinify()) {
                            $this->assets[$assetName]->minify();
                        }
                        if ($this->assets[$assetName]->getType() == Asset::SCRIPT) {
                            $separator = ";\n";
                        } else {
                            $separator = "\n";
                        }
                        $contents .= $this->assets[$assetName]->getData() . $separator;
                    }

                    self::writeFile($assetPath, $contents); //write the uncompressed file
                    if ($this->gzip) {
                        self::writeFile($assetPath . '.gz', gzencode($contents)); //write the compressed version
                    }
                    self::cleanup($assetPath);
                }
                $result .= str_replace('{assetName}', self::unroot($assetPath), $declarationTemplate);

            } else if (is_string($group)) {
                //Debug::log($group,'is string');
                $asset = $this->assets[$group];
                $assetName = $asset->getName();
                if ($asset->isExternal()) {
                    $result .= str_replace('{assetName}', $assetName, $declarationTemplate);
                    //$result.='<script type="text/javascript" src="'.$assetName.'"></script>';
                } else if ($asset->isInline()) {
                    $asset->load();
                    if ($this->minify && $asset->canMinify()) {
                        $asset->minify();
                    }
                    $result .= str_replace('{contents}', $asset->getData(), $inlineTeplate);
                    //$result.='<script type="text/javascript">'.$asset->getData().'</script>';
                } else {
                    $assetTime = $asset->getSourceTime();
                    $assetPath = str_replace('{filename}', self::calculateCombinedName([$assetName], $assetTime), $outputPathTemplate);
                    if (!file_exists($assetPath)) {
                        $asset->load();
                        if ($this->minify && $asset->canMinify()) {
                            $asset->minify();
                        }
                        self::writeFile($assetPath, $asset->getData());
                        if ($this->gzip) {
                            self::writeFile($assetPath . '.gz', gzencode($asset->getData())); //write the compressed version
                        }
                    }
                    $result .= str_replace('{assetName}', self::unroot($assetPath), $declarationTemplate);
                }
            }
        }
        return $result;
    }

    /*
     * Given the pathname of the new combined file, it finds the old combined files and deletes them
     */
    protected function cleanup($path)
    {
        $pattern = "#_[\\d]+\\.css|js$#";
        $globReq = preg_replace($pattern, '*', $path);
        $files = glob($globReq);
        foreach ($files as $file) {
            if ($file != $path && $file != $path . '.gz') {
                unlink($file);
            }
        }
    }

    protected function writeFile($filePath, $data)
    {
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, $data);
    }

    protected function calculateCombinedName($assets, $time)
    {
        return hash("crc32b", implode('', $assets)) . '_' . $time;
    }

    /**
     * remove document root from path, making it an url;
     **/
    protected function unroot($path)
    {
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
    }

    public function generateHeadAssets()
    {
        self::prepareOutput();
        return $this->result_head;
    }

    public function generateBottomAssets()
    {
        self::prepareOutput();
        return $this->result_bottom;

    }
}
