<?php
class Avane
{
    /**
     * Valut
     * 
     * Used to store the variables.
     * 
     * @var array
     */
     
    protected $vault = [];
    
    /**
     * Cache Bucket
     * 
     * We don't have to compile the same template again,
     * we can store the path of the compiled template into the bucket, and require the template directly.
     * 
     * @var array
     */
    
    protected $cacheBucket = [];
    
    /**
     * Categories Path
     * 
     * The path to the folder which stores many different categories.
     * 
     * @var string
     */
    
    public $categoriesPath = '';
    
    /**
     * Category Name
     * 
     * The name of the category which we selected.
     * 
     * @var string
     */
    
    public $categoryName = '';
    
    /**
     * PJAX Header
     * 
     * The name of the header which used to detect the PJAX request.
     * 
     * @var string
     */
    
    public $pjaxHeader = 'HTTP_X_PJAX';
    
    /**
     * Loop
     * 
     * Used to store the loop informations.
     * 
     * @var array
     */
    
    protected $loop = [];
    
    /**
     * Title
     * 
     * The title of the page.
     * 
     * @var string
     */
     
    protected $title = '';
    
    /**
     * Output Buffer
     * 
     * Stores the rendered contents.
     * 
     * @var string
     */
    
    protected $outputBuffer = [];
    
    /**
     * Is PJAX
     * 
     * True when it's a PJAX request.
     * 
     * @var bool
     */
     
    protected $isPJAX = false;
    
    /**
     * Total Time
     * 
     * Stores the time we wasted on compile, output, and fap.
     * 
     * @var int
     */
    
    protected $totalTime = 0;
    
    /**
     * Imports
     * 
     * Stores the path of the javascripts and the stylesheets.
     * 
     * @var array
     */
    
    protected $imports = [];
    
    /**
     * Ignore Sass
     * 
     * Ignore the compilation of the sass files when true.
     * 
     * @var bool
     */
     
     protected $ignoreSass = true;
     
     /**
      * Sass
      * 
      * Stores the sass paths to compile later.
      * 
      * @var array
      */
     
     protected $sass = [];
     
     /**
      * Sassc Path
      * 
      * The path of the sassc.
      * 
      * @var string
      */
     
     protected $sassc = 'sassc';
     
     protected $forceCompile = false;

    
    
    
    /**
     * CONSTRUCT
     */
     
    function __construct($thisOne = null, $configs = [])
    {
        if(!empty($configs))
        {
            $this->configs($configs);
        }
        
        if($thisOne && !is_string($thisOne)) 
        {
            foreach (get_object_vars($thisOne) as $key => $value)
                $this->$key = $value;
        }
        else
        {
            $this->categoriesPath = $thisOne;
            $this->isPJAX         = array_key_exists($this->pjaxHeader, $_SERVER) ? $_SERVER[$this->pjaxHeader] : false;
            $this->setCategory();
        }
    }
    
    
    
    
    /**
     * Set Category
     * 
     * Tell us which folder you want us to load the templates from it.
     *
     * @param string $categoryName
     * 
     * @return Avane
     */
    
    function setCategory($categoryName='default')
    {
        $this->categoryName       = $categoryName;
        $this->categoriesPath     = rtrim($this->categoriesPath, '/') . '/';
        $this->categoryPath       = $this->categoriesPath     . $categoryName . '/';
        $this->compiledPath       = $this->categoryPath       . 'compiled/';
        $this->avNamesPath        = $this->categoryPath       . 'compiled/av_names.json';
        $this->scriptsPath        = $this->categoryPath       . 'scripts/';
        $this->avScriptPath       = $this->scriptsPath        . 'avane-tags.js';
        $this->stylesPath         = $this->categoryPath       . 'styles/';
        $this->sassPath           = $this->categoryPath       . 'sass/';
        $this->templateFolderPath = $this->categoryPath       . 'tpls/';
        $this->headerPath         = $this->templateFolderPath . 'header' . $this->templateExtension;
        $this->footerPath         = $this->templateFolderPath . 'footer' . $this->templateExtension;
        $this->configPath         = $this->categoryPath       . 'config.php';
        $this->templateExtension  = '.tpl.php';
        
        return $this;
    }
    
    
    
    
    /**
     * Load
     * 
     * Load and require the template.
     * 
     * @param string $templateName   The name of the template (without the extension).
     * @param array  $variables      The variables.
     * 
     * @return Avane
     */
    
    function load($templateName, $variables = null)
    {
        $this->set($variables);
        
        /** Load the configs of the category */
        @include($this->configPath);
        
        /** Compile the template If the cache bucket doesn't have the template */
        if(!isset($this->cacheBucket[$templateName]))
            $this->setBucket($templateName, $this->compile('template', $templateName));
        
        /** Require the template from the cache bucket, so we don't need to compile it to get the path again */
        require($this->cacheBucket[$templateName]['path']);
        
        return $this;
    }
    
    
    
    
    /**
     * Set Bucket
     * 
     * Put the compiled result to the cache bucket.
     * 
     * @param string $templateName   The name of the template that we are going to store with.
     * @param string $path           The path of the compiled template.
     * 
     * @return Avane
     */
     
    function setBucket($templateName, $path)
    {
        $this->cacheBucket[$templateName] = $path;
        
        return $this;
    }
    
    

    
    /**
     * Fetch
     * 
     * Same as the load(), the only different is that fetch() returns the rendered content instead of output it.
     * 
     * @param string $templateName   The name of the template (without the extension).
     * @param array  $variables      The variables.
     * 
     * @return Avane
     */
    
    function fetch($templateName, $variables = null)
    {
        $this->capture(true);
        $this->load($templateName, $variables);
        return $this->endCapture();
    }
        
    
    
    
    /**
     * Header
     * 
     * The header of the page, and we do something here also.
     * 
     * @param 
     */
     
    function header($title = '', $variables = null)
    {
        $this->startTime = microtime(true);
        
        /** Set the title */
        $this->title = $title;
        
        /** And set the title as the avane variable */
        $this->set('title', $title);
        
        /** Load the configs of the category */
        @include($this->configPath);
        
        /** Set variables **/
        $this->set($variables);
        
        /** Set the json header if it's a PJAX request, otherwise load the header template */
        if($this->isPJAX)
            header('Content-Type: application/json; charset=utf-8');
        else
            if(!$this->ignoreSass)
                $this->compile('sass');

        /** Capture the rendered content from now on */
        $this->capture()
             /** Load the header and require it */
             ->load('header')
             /** Stop capturing, and store the captured content to the output buffer array */
             ->endCapture('header')
             /** Start another capture action for the content part */
             ->capture();
        
        return $this;
    }
    
    
    
    
    /**
     * Footer
     *
     * @return Avane
     */
     
    function footer()
    {
        /** Stop capturing, what we got here is a content-only part, store it either */
        $this->endCapture('content')
             /** Now capture the footer part */
             ->capture()
             /** Require the footer template */
             ->load('footer')
             /** And stop capturing, you know what's next right? */
             ->endCapture('footer');
        
        $this->endTime   = microtime(true);
        $this->totalTime = $this->endTime - $this->startTime;
        
        /** Return the rendered content if it's a PJAX request */
        if($this->isPJAX)
            echo json_encode($this->returnPJAX());

        //flush();
        
        return $this;
    }
    



    /**
     * Get Template Path
     * 
     * Input a template name, output the whole path to the template.
     * 
     * @param string $templateName   The name of the template.
     * 
     * @return Avane
     */
    
    function getTemplatePath($templateName)
    {
        return $this->templateFolderPath . $templateName . $this->templateExtension;
    }
    
    
    
    
    /**
     * Compile
     * 
     * Compile anything like: a template, styles, scripts.
     * 
     * @param string      $type   The type of the thing which we are going to compile with.
     * @param string|null $path   The path of the file.
     *
     * @return Avane
     */
    
    function compile($type, $path = null)
    {
        switch($type)
        {
            case 'template':
                $path = $this->getTemplatePath($path);
                $data = $this->templateCompile($path);
                break;
            case 'sass':
                $this->sassCompile();
                break;
        }
        
        return $data;
    }
    
    
    
    
    /**
     * Template Compile
     * 
     * Compile a temple.
     * 
     * @param string $templatePath   The path of the template or the raw content if we are going to compile a raw template.
     * @param bool   $raw            Set true if we are going to compile the raw content instead of a file.
     * 
     * @return array
     */
    
    function templateCompile($templatePath, $raw = false)
    {
        if(!isset($this->templateCompiler))
            $this->templateCompiler = new AvaneTemplateCompiler($this);
       
        $info = $this->templateCompiler->compile($templatePath, $raw);
        
        return ['path'    => $info['path'],
                'content' => $info['content']];
    }
    
    
    
    
    /**
     * Configs
     * 
     * Set the settings here.
     * 
     * @param array $configs   The settings.
     * 
     * @return Avane
     */
    
    function configs($configs)
    {
        foreach($configs as $name => $value)
        {
            switch($name)
            {
                case 'pjaxHeader':
                    $this->pjaxHeader = $value;
                    break;
                
                case 'forceCompile':
                    $this->forceCompile = $value;
                    break;
                
                case 'templatePath':
                    $this->categoriesPath = $value;
                    break;
                
                case 'ignoreSass':
                    $this->ignoreSass = $value;
                    break;
            }
        }
        
        return $this;
    }
    
    
    
    
    /***********************************************
    /***********************************************
    /****************** P J A X ********************
    /***********************************************
    /***********************************************
    
    /**
     * Return PJAX
     * 
     * Combine the different informations based on the PJAX header content.
     * 
     * @return array   The datas.
     */
     
    function returnPJAX()
    {
        if(!$this->isPJAX)
            return false;
            
        $types = explode(', ', $this->isPJAX);
        $data  = [];
        
        if(in_array('title', $types))
            $data['title'] = $this->title;
        
        if(in_array('html', $types))
            $data['html'] = $this->outputBuffer['header']  . 
                            $this->outputBuffer['content'] . 
                            $this->outputBuffer['footer'];
        
        if(in_array('header', $types))
            $data['header'] = $this->outputBuffer['header'];
        
        if(in_array('content', $types))
            $data['content'] = $this->outputBuffer['content'];
        
        if(in_array('footer', $types))
            $data['footer'] = $this->outputBuffer['footer'];
            
        if(in_array('wasted', $types))
            $data['wasted'] = $this->totalTime;
            
            
        
        return $data;
    }
    
    
    
    
    /***********************************************
    /***********************************************
    /**************** C A P T U R E ****************
    /***********************************************
    /***********************************************
    
    /**
     * Capture
     * 
     * Capture the rendered content after this function.
     * 
     * @param bool $force   Force capture.
     * 
     * @return Avane
     */
     
    function capture($force = false)
    {
        if(!$this->isPJAX && !$force)
            return $this;
            
        ob_start();
        
        return $this;
    }
    
    
    
    
    /**
     * End Capture
     * 
     * Stop capturing, and store the captured content to the right array.
     */
    
    function endCapture($position = null)
    {
        if(!$this->isPJAX && $position)
            return $this;
        
        switch($position)
        {
            case 'header':
                $this->outputBuffer['header']  = ob_get_clean();
                break;
            case 'content':
                $this->outputBuffer['content'] = ob_get_clean();
                break;
            case 'footer':
                $this->outputBuffer['footer']  = ob_get_clean();
                break;
            case null:
                return ob_get_clean();
                break;
        }
        
        return $this;
    }
    
    
    
    
    /***********************************************
    /***********************************************
    /****************** L O O P ********************
    /***********************************************
    /***********************************************
    
    /**
     * Loop Front
     * 
     * Initialize a new loop.
     * 
     * @param array $mainArray   The new array.
     *  
     * @return Avane
     */
    
    function loopFront($mainArray)
    {
        $this->loop[] = ['index'     => 1,
                         'index0'    => 0,
                         'revindex'  => count($mainArray),
                         'revindex0' => count($mainArray) - 1,
                         'first'     => true,
                         'last'      => count($mainArray) == 1,
                         'length'    => count($mainArray),
                         'even'      => false,
                         'odd'       => true];
        
        return $this;
    }
    
    
    
    
    /**
     * Loop Start
     * 
     * Explode this array, turn all the values to the avane variables.
     * 
     * @param array  $array   The array of 'this round'.
     * @param string $name    The name of the array.
     * 
     * @return Avane
     */
     
    function loopStart($array, $name)
    {
        $this->set($name, $array);
        
        return $this;
    }
    
    
    
    
    /**
     * Loop End
     * 
     * 'This round' is ended, now update the information of the loop, and unset those variables for this round.
     * 
     * @return Avane
     */
     
    function loopEnd()
    {
        $this->cleanSet($name);
        
        $latest = count($this->loop) - 1;

        $this->loop[$latest]['index']      += 1;
        $this->loop[$latest]['index0']     += 1;
        $this->loop[$latest]['revindex']   -= 1;
        $this->loop[$latest]['revindex0']  -= 1;
        $this->loop[$latest]['first']       = $this->loop[$latest]['index']    == 1;
        $this->loop[$latest]['last']        = $this->loop[$latest]['revindex'] == 1;
        $this->loop[$latest]['even']        = $this->loop[$latest]['index'] % 2 === 0;
        $this->loop[$latest]['odd']         = $this->loop[$latest]['index'] % 2 !== 0;
        
        return $this;
    }
    
    
    
    
    /**
     * Loop Back
     * 
     * The end of the whole loop, remove this loop from the loop array.
     * 
     * @return Avane
     */
     
    function loopBack()
    {
        array_pop($this->loop);
        
        return $this;
    }
    

    
    
    /***********************************************
    /***********************************************
    /************* V A R I A B L E S ***************
    /***********************************************
    /***********************************************
    
    /**
     * Set
     * 
     * Store a custom variable for Avane.
     * 
     * @param string $key     The name of the variable.
     * @param mixed  $value   The value of the variable.
     * 
     * @return Avane
     */
    
    function set($key, $value = null)
    {
        if(is_array($key))
            foreach($key as $name => $value)
                $this->set($name, $value);
        else
            $this->vault[$key] = $value;
        
        return $this;
    }
    
    
    
    
    /**
     * Include Set
     * 
     * Set 
     */
    
    function includeSet($name, $path)
    {
        if(is_array($name))
        {
            foreach($name as $templateName => $templatePath)
                $this->includeSet($templateName, $templatePath);
            
            return $this;
        }
            
        $data = $this->compile('template', $path);

        /** Parse the each tpl file and get their path, then store the path with this variable */
        $this->vault['TEMPLATE_INCLUDE_' . $name] = $data['path'];
        
        return $this;
    }
    
    
    
    
    /**
     * Clean Set
     * 
     * Unset an avane variable.
     * 
     * @param string $key   The name of the variable.
     * 
     * @return Avane
     */
     
    function cleanSet($key)
    {
        unset($this->vault[$key]);
        
        return $this;
    }
    



    /**
     * Get
     * 
     * Get a variable from the vault.
     * 
     * @param string $key   The name of the variable.
     * 
     * @return Avane
     */
    
    function get($key)
    {
        if($key == 'loop')
            return $this->loop[count($this->loop) - 1];
        else
            return isset($this->vault[$key]) ? $this->vault[$key] : null;
    }
    
    
    
    
    /**
     * Directive
     * 
     * Return a filtered variable.
     * 
     * @param string $value       The value.
     * @param string $directive   The name of the filter.
     * 
     * @return string
     */
    
    function directive($value, $directive)
    {
        //$directive = str_replace(' ', '', $directive);
        //$directive = '_' . $directive;
        
        return AvaneDirectives::$directive($value);
    }




    /***********************************************
    /***********************************************
    /******************* S A S S *******************
    /***********************************************
    /***********************************************    
    
    /**
     * SASS Set
     * 
     * Set a sass file and Avane will compile it.
     * 
     * @param string $styleName   The filename after we compile it.
     * @param string $path        The path to the sass file.
     */
    
    function sassSet($styleName, $path)
    {
        /** Don't ignore the compilation of sass once we setted a sass to compile */
        $this->ignoreSass = false;
        
        $this->sass[$styleName] = $path;
        
        return $this;
    }
    
    
    
    
    /**
     * Sass Compile
     */
    
    function sassCompile()
    {
        if(!isset($this->sassCompiler))
            $this->sassCompiler = new AvaneSassCompiler($this);
       
        $this->sassCompiler->compile();
        
        return $this;
    }
    
    
    
    
    /***********************************************
    /***********************************************
    /********* L I N K S & S C R I P T S ***********
    /***********************************************
    /***********************************************
    
    /**
     * Import
     * 
     * Import the stylesheets or the scripts.
     * 
     * @param string $group      The group name of the file.
     * @param string $path       The path of the file.
     * @param bool   $inFolder   Set true if the file is in the template folder.
     * 
     * @return Avane
     */
    
    function import($group, $path, $inFolder = false)
    {
        $hasGroup = isset($this->imports[$group]);
        $pathType = pathinfo($path)['extension'];
        
        switch($pathType)
        {
            case 'js': 
                $path  = $inFolder ? $this->scriptsPath . $path : $path;
                break;
            
            case 'css':
                $path  = $inFolder ? $this->stylesPath  . $path : $path;
                break;
        }
        
        if(!$hasGroup)
            $this->imports[$group] = [];
        
        $this->imports[$group][$path] = $pathType;

        return $this;
    }
    
    
    
    
    /**
     * Output
     * 
     * Output all the files which are within the specify group.
     * 
     * @param string $group   The group name.
     * 
     * @return Avane
     */
    
    function output($group)
    {
        if(!isset($this->imports[$group]))
            return false;
        
        foreach($this->imports[$group] as $path => $type)
        {
            $type = strtolower($type);
            
            switch($type)
            {
                case 'js':
                    echo "<script src=\"$path\"></script>\n";
                    break;
                
                case 'css':
                    echo "<link rel=\"stylesheet\" href=\"$path\">\n";
                    break;
            }
        }
        
        return $this;
    }
}
?>