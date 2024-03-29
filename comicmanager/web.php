<?php


namespace datagutten\comicmanager;


use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\exceptions\comicManagerException;
use Exception;
use Throwable;
use Twig;

class web extends comicmanager
{
    /**
     * @var Twig\Environment
     */
    public Twig\Environment $twig;
    /**
     * @var string Web site root directory
     */
    public string $root;

    function __construct(array $config = null)
    {
        if($this->debug)
            ini_set('display_errors', true);

        $loader = new Twig\Loader\FilesystemLoader(array(__DIR__.'/../templates', __DIR__.'/../management/templates'), __DIR__);
        $this->twig = new Twig\Environment($loader, array('debug' => $this->debug, 'strict_variables' => true));

        try
        {
            parent::__construct($config);
        }
        catch (Exception $e)
        {
            $this->root = $config['web_root'] ?? '/comicmanager';
            die($this->render_exception($e));
        }
        $this->root = $this->web_root;
    }

    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     *
     */
    public function render(string $name, array $context): string
    {
        $context['root'] = $this->root;
        if(!empty($this->info))
            $context['comic'] = $this->info;

        try {
            return $this->twig->render($name, $context);
        }
        catch (Twig\Error\Error $e) {
            $msg = "Error rendering template:\n" . $e->getMessage();
            try {
                $context += [
                    'title'=>'Rendering error',
                    'error'=>$msg,
                    'trace'=>$e->getTraceAsString()
                ];

                die($this->twig->render('error.twig', $context));
            }
            catch (Twig\Error\Error $e_e)
            {
                $msg = sprintf("Original error: %s\n<pre>%s</pre>\nError rendering error template: %s\n<pre>%s</pre>",
                    $e->getMessage(), $e->getTraceAsString(), $e_e->getMessage(), $e_e->getTraceAsString());
                die($msg);
            }
            //die($this->render($this->render()))
        }
    }

    public function render_error(string $error): string
    {
        return $this->render('error.twig', ['error' => $error, 'title'=>'error']);
    }

    public function render_exception(Throwable $throwable): string
    {
        return $this->render('exception.twig', ['e' => $throwable, 'title'=>'exception', 'show_trace'=>$this->debug ? 'true': '']);
    }

    /**
     * Display links to select a comic
     * @return string
     */
    public function select_comic(): string
    {
        try {
            $context = array(
                'comics' => $this->comic_list(),
                'title' => 'Select comic',
                'root' => $this->root);
            return $this->render('select_comic.twig', $context);
        }
        catch (comicManagerException $e)
        {
            return $this->render('exception.twig', array('e'=>$e));
        }
    }

    /**
     * Call comicinfo with argument from GET parameter comic or show comic selection
     * @return Comic|bool
     * @throws exceptions\ComicNotFound Comic not found
     * @throws exceptions\DatabaseException Database error
     */
    public function comicinfo_get()
    {
        if(isset($_GET['comic']))
        {
            if(isset($_GET['keyfield'])) //Override default key field for the comic
                return $this->comicinfo($_GET['comic'],$_GET['keyfield']);
            else
                return $this->comicinfo($_GET['comic']);
        }
        else //No comic selected, display comic selection
        {
            echo $this->select_comic();
            return false;
        }
    }
}