<?php
/**
 * LoggerSubscriber.php
 * @author Andrea Giuliano <giulianoand@gmail.com>
 *         Date: 17/10/12
 */
namespace Shark\FormLoggerBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\Form;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpFoundation\Session\Session;

class LoggerSubscriber implements EventSubscriberInterface
{
    private $logPath;
    private $session;
    private $log;

    public function __construct($path, Session $session)
    {
        $this->logPath = $path;
        $this->session = $session;
    }
    public static function getSubscribedEvents()
    {
        return array(FormEvents::POST_BIND => 'logData');
    }

    /**
     * @param DataEvent $event
     */
    public function logData(DataEvent $event)
    {
        $form = $event->getForm();


        $this->logForm($form);
    }

    public function logForm(Form $form)
    {
        $formName = $form->getName();

        $this->generateLog($formName);

        foreach($form->all() as $elem)
        {
            if ($elem->hasChildren()) {
                foreach ($elem->getChildren() as $child)
                {
                    $this->logElem($child, $this->prefixify($elem->getName()));
                }
            } else {
                $this->logElem($elem);
            }

        }

    }

    protected  function logElem($elem, $prefix = null)
    {
        $token = substr($this->session->getId(), 0, 8);
        $errorString = implode(', ', $this->getFiedErrorsAsString($elem));
        $error = sprintf("[%s%s] => '%s' [Errors: %s]", $prefix, $elem->getName(), $elem->getViewData(), $errorString);
        $this->log->addError(sprintf("[%s] : %s", $token, $error));
    }

    protected function generateLog($name)
    {
        $this->log = new Logger('shark.form');
        if (!file_exists(sprintf("%s/%s.log", $this->logPath, $name)))
        {
            touch(sprintf("%s/%s.log", $this->logPath, $name));
        }
        $this->log->pushHandler(new StreamHandler(sprintf("%s/%s.log", $this->logPath, $name), Logger::WARNING));
    }

    protected function getFiedErrorsAsString($field)
    {
        $errors = array();
        foreach ($field->getErrors() as $error)
        {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }

    protected function prefixify($prefix)
    {
        return sprintf("%s_", $prefix);
    }


}