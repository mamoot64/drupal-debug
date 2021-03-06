<?php

namespace Ekino\Drupal\Debug\Action;

use Ekino\Drupal\Debug\Event\DebugKernelEvents;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\Debug\ExceptionHandler;

class DisplayPrettyExceptionASAPAction implements EventSubscriberActionInterface
{
    /**
     * @var string|null
     */
    private $charset;

    /**
     * @var string|null
     */
    private $fileLinkFormat;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DebugKernelEvents::AFTER_ENVIRONMENT_BOOT => 'process',
        );
    }

    /**
     * @param string|null $charset
     * @param string|null $fileLinkFormat
     */
    public function __construct($charset, $fileLinkFormat)
    {
        $this->charset = $charset;
        $this->fileLinkFormat = $fileLinkFormat;
    }

    public function process()
    {
        set_exception_handler(function (\Throwable $exception) {
            if (!$exception instanceof \Exception) {
                $exception = new FatalThrowableError($exception);
            }

            $exceptionHandler = new ExceptionHandler(true, $this->charset, $this->fileLinkFormat);
            $exceptionHandler->sendPhpResponse($exception);
        });
    }

    /**
     * @param string $appRoot
     *
     * @return DisplayPrettyExceptionASAPAction
     */
    public static function getDefaultAction($appRoot)
    {
        return new self(null, null);
    }
}
