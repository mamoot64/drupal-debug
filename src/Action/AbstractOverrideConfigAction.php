<?php

namespace Ekino\Drupal\Debug\Action;

use Drupal\Core\Cache\NullBackendFactory;
use Ekino\Drupal\Debug\Event\DebugKernelEvents;
use Ekino\Drupal\Debug\Helper\ConfigHelper;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractOverrideConfigAction implements EventSubscriberActionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DebugKernelEvents::AFTER_SETTINGS_INITIALIZATION => 'process',
        );
    }

    public function process()
    {
        global $config;

        $propertyAccessor = new PropertyAccessor();
        foreach ($this->getOverrides() as $propertyPath => $value) {
            $propertyAccessor->setValue($config, $propertyPath, $value);
        }
    }

    /**
     * @return array
     */
    abstract protected function getOverrides();
}
