<?php

namespace Ekino\Drupal\Debug\Kernel;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Logger\LoggerChannel;
use Ekino\Drupal\Debug\Action\ActionInterface;
use Ekino\Drupal\Debug\Action\EnhanceClassLoaderAction;
use Ekino\Drupal\Debug\Action\EnhanceContainerAction;
use Ekino\Drupal\Debug\Action\EnhanceExceptionPageAction;
use Ekino\Drupal\Debug\Action\RegisterExceptionHandlerAction;
use Ekino\Drupal\Debug\Action\ThrowErrorsAsExceptionsAction;
use Ekino\Drupal\Debug\Action\WatchHooksAction;
use Ekino\Drupal\Debug\Event\ContainerEvent;
use Ekino\Drupal\Debug\Event\DebugKernelEvents;
use Ekino\Drupal\Debug\Manager\ActionManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\Logger;

class DebugKernel extends DrupalKernel
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ActionManager
     */
    private $actionManager;

    /**
     * @param string $environment
     * @param object $class_loader
     * @param bool $allow_dumping
     * @param sg|trinnull $app_root
     * @param ActionInterface[] $actions
     */
    public function __construct($environment, $class_loader, $allow_dumping = true, $app_root = null, array $actions = array())
    {
        $this->eventDispatcher = new EventDispatcher();

        $appRoot = $app_root;
        if (!is_string($appRoot)) {
            $appRoot = static::guessApplicationRoot();
        }

        $this->actionManager = new ActionManager($appRoot);

        $this->actionManager->process($actions);

        $this->actionManager->addEventSubscriberActionsToEventDispatcher($this->eventDispatcher);

        $this->eventDispatcher->dispatch(DebugKernelEvents::ON_KERNEL_INSTANTIATION);

        static::bootEnvironment();

        $this->eventDispatcher->dispatch(DebugKernelEvents::AFTER_ENVIRONMENT_BOOT);

        parent::__construct($environment, $class_loader, $allow_dumping, $appRoot);
    }

    /**
     * {@inheritdoc}
     */
    public function preHandle(Request $request)
    {
        parent::preHandle($request);

        $this->eventDispatcher->dispatch(DebugKernelEvents::AFTER_REQUEST_PRE_HANDLE, new ContainerEvent($this->container));
    }

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        return array_merge(parent::getKernelParameters(), array(
            'kernel.debug' => true
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        $container = parent::initializeContainer();

        $this->eventDispatcher->dispatch(DebugKernelEvents::AFTER_CONTAINER_INITIALIZATION, new ContainerEvent($container));

        return $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeSettings(Request $request)
    {
        parent::initializeSettings($request);

        $this->eventDispatcher->dispatch(DebugKernelEvents::AFTER_SETTINGS_INITIALIZATION);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerBuilder()
    {
        $containerBuilder = parent::getContainerBuilder();

        $this->actionManager->addCompilerPassActionsToContainerBuilder($containerBuilder);

        return $containerBuilder;
    }
}
