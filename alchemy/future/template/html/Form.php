<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\html;
use alchemy\storage\Session;
use alchemy\future\template\html\form\Hidden;
class Form implements \Iterator
{
    /**
     * Experimental form helper
     *
     * Creates form with salt item
     * Always sent forms through POST
     *
     * @example Code example
     * $form = new Form('myForm');
     * $form->inputName = new TextInput('Some label');
     * $form->anotherField = new TextInput('Another label');
     *
     * //checking if form was submited
     * if ($form->isSubmited()) {
     *  //do something
     * }
     *
     * @example view example
     * <form method="POST" action=".">
     *     <?php echo $form; //this will include salt input ?>
     *     <?foreach($form as $name=>$item):?>
     *         <label for="<?=$name;?>"><?=$item->getLabel();?></label><?=$item;?>
     *     <?endforeach;?>
     * </form>
     *
     * The above example should generate:
     * <form method="POST" action=".">
     *  <input type="hidden" name="sdf2923wuifwfw..." value="1" />
     *  <label for="inputName">Some label</label><input type="text" name="inputName" />
     *  <label for="anotherField">Another label</label><input type="text" name="anotherField" />
     * </form>
     *
     * @param null $salt
     */
    public function __construct($name, $salt = null)
    {
        $this->session = &Session::get('AlchemyFormHelper:' . $name);
        $this->previousSalt = $this->session['salt'];
        if (!$salt) {
            $salt = sha1(time() . mt_rand(1, 1000));
        }
        $input = new Hidden();
        $input->setName($salt);
        $input->setValue(1);

        $this->session['salt'] = $salt;
        $this->saltItem = $input;

    }

    /**
     * Gets form item
     *
     * @param $name
     * @return \alchemy\future\template\html\form\Input
     */
    public function __get($name)
    {
        return $this->input[$name];
    }

    /**
     * Sets new form item
     * @param $name
     * @param form\Input $value
     */
    public function __set($name, form\Input $value)
    {
        $value->setName($name);
        $this->input[$name] = $value;
    }

    //Iterator
    public function rewind()
    {
        reset($this->input);
    }

    public function current()
    {
        return current($this->input);
    }

    public function key()
    {
        return key($this->input);
    }

    public function next()
    {
        return next($this->input);
    }

    public function valid()
    {
        return current($this->input) instanceof form\Input;
    }
    //\Iterator

    /**
     * Checks if form was send
     */
    public function isSubmited()
    {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$this->previousSalt]) && $_POST[$this->previousSalt] == 1) {
            return true;
        }
        return false;
    }

    public function isValid()
    {
        $valid = true;
        $this->fetch($_POST);
        foreach ($this->input as $i)
        {
            if(!$i->validate()) {
                $this->lastError = $i->getValidator()->getMessage();
                $valid = false;
            }
        }
        return $valid;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function fetch($data)
    {
        if (is_object($data)) $data = get_object_vars($data);
        foreach ($data as $name => $value)
        {
            if (isset($this->input[$name])) {
                $this->input[$name]->setValue($value);
            }
        }
    }

    public function __toString()
    {
        return '' . $this->saltItem;
    }

    protected $session;
    protected $previousSalt;
    protected $lastError;
    protected $input;

    /**
     * @var \alchemy\future\template\html\form\Hidden
     */
    private $saltItem;
}