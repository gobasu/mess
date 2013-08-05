<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;
class CompilerException extends \Exception {}
class Compiler
{
    public function __construct($fileName = '')
    {
        $this->fileName = $fileName;
        $this->setContext(self::MAIN_FUNCTION_NAME);
    }

    public function compile(Node $tree)
    {
        foreach ($tree->getChildren() as $node) {
            if ($node->getType() == Node::NODE_TEXT) {
                //escape <?php and <? from code
                $this->appendText(str_replace('<?', '&lt;?', $node->getValue()));
                continue;
            }

            $handler = $node->getHandler();
            $handler = new $handler($node);
            $handler->handle($this);

            if ($node->hasChildren()) {
                $this->compile($node);
            }

        }
    }

    public static function getTemplateClassName($file)
    {
        return self::TEMPLATE_CLASS_SUFFIX . sha1($file);
    }

    public function getOutput($className)
    {
        ob_start();
        //add use statements
        echo '<?php use alchemy\template\Mixture; use \alchemy\template\mixture\Template;' . PHP_EOL;
        echo '//filename:' . $this->fileName . PHP_EOL;

        if ($this->dependencyFile) {
            echo 'Template::load(\'' . $this->dependencyFile . '\'); ' . PHP_EOL;
        }
        echo 'class ' . $className . ' extends ' . $this->extends . ' {' . PHP_EOL;

        foreach ($this->source as $methodName => $content) {
            if ($this->dependencyFile && $methodName == self::MAIN_FUNCTION_NAME) {//ommit render function for children templates
                continue;
            } elseif ($methodName == self::MAIN_FUNCTION_NAME) {
                //main render method should not echo the template but return result as a string
                echo    PHP_EOL . 'public function ' . $methodName . '() {' . PHP_EOL . 'ob_start();?>' . $content .
                        '<?php $renderedTemplate = ob_get_contents(); ob_end_clean(); return $renderedTemplate;' . PHP_EOL . '}';
            } else {
                echo 'public function ' . $methodName . '() {' . PHP_EOL . '?>' . $content . '<?php' . PHP_EOL . ' }';
            }
        }

        echo PHP_EOL . '}' . PHP_EOL;

        $class = ob_get_contents();
        ob_end_clean();

        return $class;
    }

    public function appendText($text)
    {
        $this->source[$this->context] .= $text;
    }

    public function setText($text)
    {
        $this->source[$this->context] = $text;
    }

    public function setContext($name)
    {
        if ($this->context) {
            $this->lastContext[] = $this->context;
        }
        $this->context = $name;
        if (!isset($this->source[$name])) {
            $this->source[$name] = '';
        }
    }

    public function removeContext($name)
    {
        unset($this->source[$name]);
    }

    public function gotoLastContext()
    {
        $context = array_pop($this->lastContext);
        if (!$context) {
            $context = self::MAIN_FUNCTION_NAME;
        }
        $this->context = $context;
    }

    public function setExtends($name)
    {
        $file = Template::getTemplateFileName($name);
        if (!is_readable($file)) {
            throw new CompilerException('Cannot extend unexistent template file ' . $name);
        }
        $this->extends = self::getTemplateClassName($file);
        $this->dependencyFile = $name;
    }

    protected $context = self::MAIN_FUNCTION_NAME;
    protected $lastContext = array();
    protected $source = array();
    protected $extends = self::TEMPLATE_EXTENDS;
    protected $dependencyFile;
    protected $fileName;


    /**
     * $source = array(
        'render' <---main function
     * 'name' <--- other functions
     * )
     */
    const TEMPLATE_EXTENDS = 'Template';
    const MAIN_FUNCTION_NAME = 'render';
    const TEMPLATE_CLASS_SUFFIX = 'MixtureTemplate';
}
